<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}


function nowpayments_MetaData()
{
    return array(
        'DisplayName' => 'NOWPayments',
        'APIVersion' => '1.2', // Use API Version 1.1
    );
}


function nowpayments_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'NOWPayments',
        ),
        'apiKey' => array(
            'FriendlyName' => 'API key',
            'Type' => 'text',
            'Default' => '',
            'Description' => 'Enter your API key here',
        ),
        'ipnSecret' => array(
            'FriendlyName' => 'IPN Secret',
            'Type' => 'text',
            'Default' => '',
            'Description' => 'Enter your IPN Secret here',
        )
    );
}


function nowpayments_link($params)
{
    $origin = $_SERVER['HTTP_ORIGIN'];
    $path = array_filter(explode('/', parse_url($_SERVER['REQUEST_URI'])['path']));
    $logoUrl = $params['systemurl'] . 'modules/gateways/nowpayments/logo.png';
    $ipnUrl = $params['systemurl'] . 'modules/gateways/callback/nowpayments.php';
    if(empty($params['systemurl'])) {
        if(count($path) > 1) {
            array_pop($path);
            $prefix = implode('/', $path);
            $logoUrl = '/' . $prefix . '/modules/gateways/nowpayments/logo.png';
            $ipnUrl = $origin . '/' . $prefix . '/modules/gateways/callback/nowpayments.php';
        } else {
            $logoUrl = '/modules/gateways/nowpayments/logo.png';
            $ipnUrl = $origin . '/modules/gateways/callback/nowpayments.php';
        }
    }

    $orderId = 'WHMCS-' . $params['invoiceid'];
    $nowpaymentsArgs = [
        'ipnURL' => $ipnUrl,
        'successURL' => $params['systemurl'].'viewinvoice.php?id='.$params['invoiceid'].'&paymentsuccess=true',
        'cancelURL' => $params['systemurl'].'viewinvoice.php?id='.$params['invoiceid'].'&paymentfailed=true',
        'dataSource' => 'whmcs',
        'paymentCurrency' => mb_strtoupper($params['currency']),
        'apiKey' => $params['apiKey'],
        'customerName' => $params['clientdetails']['firstname'],
        'customerEmail' => $params['clientdetails']['email'],
        'paymentAmount' => $params['amount'],
        'orderID' => $orderId
    ];

    $url = 'https://nowpayments.io/payment?data=';
    $nowpayments_adr = $url . urlencode(json_encode($nowpaymentsArgs));
    $htmlOutput = '<a href="' . $nowpayments_adr . '" target="_blank">';
    $htmlOutput .= '<img  src="'.$logoUrl.'" alt="NOWPayments" />';
    $htmlOutput .= ' </a>';

    return $htmlOutput;
}
