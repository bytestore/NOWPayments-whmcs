<?php

use WHMCS\Billing\Invoice;
use WHMCS\Billing\Payment\Transaction;

// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../init.php';
App::load_function('gateway');
App::load_function('invoice');

// Detect module name from filename.
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);

//Die if module is not active.
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

$post = file_get_contents('php://input');

logTransaction($gatewayParams['name'], $post, 'Request accepted from NOWPayments.');

function checkIpnRequest()
{
    global $gatewayParams;
    global $post;
    if (isset($_SERVER['HTTP_X_NOWPAYMENTS_SIG']) && !empty($_SERVER['HTTP_X_NOWPAYMENTS_SIG'])) {
        $recived_hmac = $_SERVER['HTTP_X_NOWPAYMENTS_SIG'];
        $request_json = $post;
        $request_data = json_decode($request_json, true);
        ksort($request_data);
        $sorted_request_json = json_encode($request_data);
        if ($request_json !== false && !empty($request_json)) {
            $hmac = hash_hmac("sha512", $sorted_request_json, trim($gatewayParams['ipnSecret']));

            if ($hmac == $recived_hmac) {
                return true;
            } else {
                logTransaction($gatewayParams['name'], $post, 'HMAC signature does not match');
                die('HMAC signature does not match');
            }
        } else {
            logTransaction($gatewayParams['name'], $post, 'Error reading POST data');
            die('Error reading POST data');
        }
    } else {
        logTransaction($gatewayParams['name'], $post, 'No HMAC signature sent.');
        die('No HMAC signature sent');
    }
}
$success = checkIpnRequest();

# Get Returned Variables - Adjust for Post Variable Names from your Gateway's Documentation
$requestJson = file_get_contents('php://input');
$requestData = json_decode($requestJson, true);
$invoiceId = str_replace('WHMCS-', '', $requestData['order_id']);
$transactionId = $requestData['payment_id'];
$priceAmount = $requestData['price_amount'];
$paymentAmount = $requestData['pay_amount'];
$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);
$paymentId = $requestData['payment_id'];

//updateInvoice if IpnRequest done
$invoice = Invoice::find($invoiceId);
if (is_null($invoice)) {
    die('IPN Error: '.'No invoice found');
}

if ($success) {
    $status = $requestData["payment_status"];

    if ($status == "finished" || $status == "confirmed") {
        $currency = $requestData['pay_currency'];
        $upper = mb_strtoupper($currency);
        $message = "Invoice ${invoiceId} has been paid. Amount received: ${paymentAmount} ${upper}. Status: ${status}";
        $invoice->addPaymentIfNotExists($priceAmount, $transactionId, 0, $gatewayModuleName);
        logTransaction($gatewayParams['name'], $post, $message);

    } else if ($status == "partially_paid") {
        $actuallyPaid = $requestData["actually_paid"];
        $actuallyPaidAtFiat = $requestData["actually_paid_at_fiat"];
        //add funds. if 0 recieved die
        $invoice->addPayment($actuallyPaidAtFiat, $transactionId, 0, $gatewayModuleName);
        $currency = $requestData['pay_currency'];
        $upper = mb_strtoupper($currency);
        $priceCurrency = $requestData['price_currency'];
        $currencyupper = mb_strtoupper($priceCurrency);
        $message = "Invoice ${invoiceId} is partially paid. Received: ${paymentAmount} ${upper} - ${actuallyPaidAtFiat} ${currencyupper}. Payment ID: ${paymentId}";
        logTransaction($gatewayParams['name'], $post, $message);
        // Set invoice status to unpaid if invoice has a balance
        if ($invoice->getBalanceAttribute()) {
                $invoice->status = 'Unpaid';
                $invoice->save();
        }
    } else if ($status == "confirming") {
        // Set invoice status to pending if invoice status confirming
        if ($invoice->getBalanceAttribute()) {
                $invoice->status = 'Payment Pending';
                $invoice->save();
        }
        logTransaction($gatewayParams['name'], $post, 'Order is processing (confirming).');

    } else if ($status == "sending") {
        logTransaction($gatewayParams['name'], $post, 'Order is processing (sending).');
    } else if ($status == "failed") {
        logTransaction($gatewayParams['name'], $post, 'Order is failed.');
    } else if ($status == "waiting") {
        logTransaction($gatewayParams['name'], $post, 'Waiting for payment.');
    }
} else {
    die('IPN Verification Failure');
}
