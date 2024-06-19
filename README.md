# WHMCS NOWPayments Gateway Module #

## Installation ##

1. Sign up for a NOWPayments account https://nowpayments.io/
2. Add your crypto wallet address for withdraw and generate `API Key`
3. In `Settings->Payments->Instant` payment notifications generate `IPN Secret`
4. Install WHMCS plugin
5. Activate plugin and configure `API` key and `IPN secret`

More info on https://nowpayments.io/whmcs-plugin

## Path ##
```
 modules/gateways/
  |- nowpayments/logo.php
  |- nowpayments/whmcs.json
  |- callback/nowpayments.php
  |  nowpayments.php
```

## Updates ##

* 14.2024 Added payment when NOWpayments status confirmed
* 14.2024 Added status of invoice to pending when NOWpayments status confirming
* 14.2024 Added correct Success and Failed URLs when a user returns from the payment system in WHMCS
* 14.2024 WHMCS Transaction logging errors fixed

## Tested on ##

* 14.2024 whmcs 8.8.0

