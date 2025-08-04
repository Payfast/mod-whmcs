<?php

require_once __DIR__ . '/payfast/vendor/autoload.php';
use Payfast\PayfastCommon\PayfastCommon;

/**
 * payfast.php
 *
 * Copyright (c) 2024 Payfast (Pty) Ltd
 * You (being anyone who is not Payfast (Pty) Ltd) may download and use this plugin /
 * code in your own website in conjunction with a registered and active Payfast account.
 * If your Payfast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin /
 * code or part thereof in any way.
 *
 */
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Define module related meta data.
 *
 * Values returned here are used to determine module related capabilities and
 * settings.
 *
 * @see https://developers.whmcs.com/payment-gateways/meta-data-params/
 *
 * @return array
 */
function Payfast_MetaData(): array
{
    //Module Definitions for use with Payfast Common File

    global $CONFIG;

    define("PF_SOFTWARE_NAME", 'WHMCS');

    define('PF_SOFTWARE_VER', $CONFIG['Version']);

    define("PF_MODULE_NAME", 'Payfast-WHMCS');
    define("PF_MODULE_VER", '2.3.1');

    return array(
        'DisplayName'                 => 'Payfast',
        'APIVersion'                  => '1.1', // Use API Version 1.1
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage'            => false, //  _storeremote takes CC details and returns token via gateway API
    );
}

/**
 * Define gateway configuration options.
 *
 * The fields you define here determine the configuration options that are
 * presented to administrator users when activating and configuring your
 * payment gateway module for use.
 *
 * Supported field types include:
 * * text
 * * password
 * * yesno
 * * dropdown
 * * radio
 * * textarea
 *
 * Examples of each field type and their possible configuration parameters are
 * provided in the sample function below.
 *
 * @return array
 */
function Payfast_config(): array
{
    return array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName'     => array(
            'Type'  => 'System',
            'Value' => 'Payfast',
        ),
        // Merchant ID field
        'merchant_id'      => array(
            'FriendlyName' => 'Merchant ID',
            'Type'         => 'text',
            'Size'         => '25',
            'Default'      => '',
            'Description'  => 'Your Merchant ID as given on the
 <a href="https://my.payfast.io/login">Integration</a> page of your Payfast account',
        ),
        // Merchant Key field
        'merchant_key'     => array(
            'FriendlyName' => 'Merchant Key',
            'Type'         => 'text',
            'Size'         => '25',
            'Default'      => '',
            'Description'  => 'Your Merchant Key as given on the
 <a href="https://my.payfast.io/login">Integration</a> page of your Payfast account',
        ),
        // PassPhrase field
        'passphrase'       => array(
            'FriendlyName' => 'PassPhrase',
            'Type'         => 'text',
            'Size'         => '32',
            'Default'      => '',
            'Description'  => 'Your PassPhrase as when set on the
 <a href="https://my.payfast.io/login">Integration</a> page of your Payfast account',
        ),
        // Recurring option
        'enable_recurring' => array(
            'FriendlyName' => 'Enable Recurring Billing',
            'Type'         => 'yesno',
            'Description'  => 'Check to enable Recurring Billing after enabling adhoc Payments on the
 <a href="https://my.payfast.io/login">Integration</a> page of your Payfast account',
        ),
        // Force Recurring option
        'force_recurring'  => array(
            'FriendlyName' => 'Force Recurring Billing',
            'Type'         => 'yesno',
            'Description'  => 'Check to force all clients to use tokenized billing(adhoc subscriptions).
             This requires "Enable Recurring Billing" to be enabled to take effect.',
        ),
        'adhoc_timer'  => array(
            'FriendlyName' => 'ADHOC Payment Wait Time',
            'Type'         => 'dropdown',
            "Options"      => "10,15,20,25,30",
            'Default'      => '15',
            'Description'  => 'Set the timeout for tokenized payments to update the Transaction Fees column.',
        ),
        // Sandbox option
        'test_mode'        => array(
            'FriendlyName' => 'Sandbox Test Mode',
            'Type'         => 'yesno',
            'Description'  => 'Check to enable sandbox mode',
        ),
        // Debugging option
        'debug'            => array(
            'FriendlyName' => 'Debugging',
            'Type'         => 'yesno',
            'Description'  => 'Check this to turn debug logging on',
        ),
    );
}

/**
 * Redirect to Payfast_link on Product/Service purchase instead of asking for card details
 */
function Payfast_nolocalcc(): void
{
}

/**
 * Remote input.
 *
 * Called when a pay method is requested to be created or a payment is
 * being attempted.
 *
 * New pay methods can be created or added without a payment being due.
 * In these scenarios, the amount parameter will be empty and the workflow
 * should be to create a token without performing a charge.
 *
 * @return string
 * @see https://developers.whmcs.com/payment-gateways/remote-input-gateway/
 */
function Payfast_remoteinput(): string
{
    return "<div class=\"alert alert-info\">A new card can only be added when paying an invoice. </div>";
}

/**
 * Remote update.
 *
 * Called when a pay method is requested to be updated.
 *
 * The expected return of this function is direct HTML output. It provides
 * more flexibility than the remote input function by not restricting the
 * return to a form that is posted into an iframe. We still recommend using
 * an iframe where possible and this sample demonstrates use of an iframe,
 * but the update can sometimes be handled by way of a modal, popup or
 * other such facility.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @return string
 * @see https://developers.whmcs.com/payment-gateways/remote-input-gateway/
 */
function Payfast_remoteupdate(array $params): string
{
    if (!$params["gatewayid"]) {
        return "<p align=\"center\">You must pay your first invoice via credit card before you can view details here...</p>";
    }

    return "";
}

/**
 * Creates payment button redirects to Payfast.
 *
 * Nested function used inside Payfast_link only.
 *
 * @param array $pfData Payment Parameters
 * @param bool $isRecurring
 * @param string $url Payfast URL To Post To
 * @param string $passphrase
 *
 * @return string
 */
function pf_create_button(array $pfData, bool $isRecurring, string $url, string $passphrase): string
{
    // Create output string
    $pfOutput = '';
    foreach ($pfData as $key => $val) {
        $pfOutput .= $key . '=' . urlencode(trim($val)) . '&';
    }

    if (empty($passphrase)) {
        $pfOutput = substr($pfOutput, 0, -1);
    } else {
        $pfOutput = $pfOutput . "passphrase=" . urlencode($passphrase);
    }

    $pfData['signature'] = md5($pfOutput);

    $pfHtml = '<form method="post" action="' . $url . '">';
    foreach ($pfData as $k => $v) {
        $pfHtml .= '<input type="hidden" name="' . $k . '" value="' . $v . '" />';
    }
    $buttonValue = $isRecurring ? 'Subscribe Using Payfast' : 'Pay Using Payfast';
    $pfHtml  .= '<input type="submit" value="' . $buttonValue . '"/></form>';

    return $pfHtml;
}

/**
 * Payment link that redirects to Payfast.
 *
 * Required by third party payment gateway modules only.
 *
 * Defines the HTML output displayed on an invoice. Typically consists of an
 * HTML form that will take the user to the payment gateway endpoint.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @return string
 * @see https://developers.whmcs.com/payment-gateways/third-party-gateway/
 *
 */
function Payfast_link(array $params): string
{
    // Payfast Configuration Parameters
    $merchantId      = $params['merchant_id'];
    $merchantKey     = $params['merchant_key'];
    $passphrase      = $params['passphrase'];
    $enableRecurring = $params['enable_recurring'];
    $forceRecurring  = $params['force_recurring'];

    // Invoice Parameters
    $invoiceId        = $params['invoiceid'];
    $description      = $params["description"];
    $amount           = $params['amount'];
    $baseCurrencyCode = $params['basecurrency'];

    // Client Parameters
    $firstname = $params['clientdetails']['firstname'];
    $lastname  = $params['clientdetails']['lastname'];
    $email     = $params['clientdetails']['email'];
    $pfToken   = $params['clientdetails']['gatewayid'];

    // System Parameters
    $systemUrl    = rtrim($params['systemurl'], '/');
    $returnUrl    = $params['returnurl'];
    $whmcsVersion = $params['whmcsVersion'];

    //Cleanup Payfast Tokens stored in tblhosting
    if (empty($pfToken)) {
        cleanupTblHosting($params);
    }

    $pfHost = (($params['test_mode'] == 'on') ? 'sandbox' : 'www') . '.payfast.co.za';
    $url    = 'https://' . $pfHost . '/eng/process';

    if (($params['test_mode'] == 'on') && (empty($params['merchant_id']) || empty($params['merchant_key']))) {
        $merchantId  = '10004002';
        $merchantKey = 'q1cd2rdny4a53';
        $passphrase  = 'payfast';
    }

    // Construct data for the form
    $data = array(
        // Merchant details
        'merchant_id'      => $merchantId,
        'merchant_key'     => $merchantKey,
        'return_url'       => $returnUrl,
        'cancel_url'       => $returnUrl,
        'notify_url'       => $systemUrl . '/modules/gateways/callback/payfast.php',

        // Buyer Details
        'name_first'       => trim($firstname),
        'name_last'        => trim($lastname),
        'email_address'    => trim($email),

        // Item details
        'm_payment_id'     => $invoiceId,
        'amount'           => number_format($amount, 2, '.', ''),
        'item_name'        => $params['companyname'] . ' purchase, Invoice ID #' . $params['invoiceid'],
        'item_description' => $description,
        'custom_str1'      => 'PF_WHMCS_' . substr($whmcsVersion, 0, 5) . '_' . PF_MODULE_VER,
        'custom_str2'      => $baseCurrencyCode,
    );

    //Create Payfast button/s on Invoice
    $htmlOutput   = '';
    $isRecurring = false;

    if ($enableRecurring && empty($pfToken)) {
        if (!$forceRecurring) {
            //Create once-off button
            $htmlOutput = pf_create_button($data, false, $url, $passphrase);
        }

        //Set button data to Payfast Subscription
        $data['subscription_type'] = 2;
        $isRecurring               = true;
    }
    //Append Payfast button
    $htmlOutput .= pf_create_button($data, $isRecurring, $url, $passphrase);

    return $htmlOutput;
}

/**
 * Capture Payfast adhoc payment.
 *
 * Called when a payment is to be processed and captured.
 *
 * The card cvv number will only be present for the initial cardholder present
 * transactions. Automated recurring capture attempts will not provide it.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @return array Transaction response status
 * @see https://developers.whmcs.com/payment-gateways/merchant-gateway/
 *
 */
function Payfast_capture(array $params): array
{
    App::load_function('gateway');
    // Detect module name from filename.

    // Instantiate the PayfastCommon class
    $payfastCommon = new PayfastCommon(true);

    // Fetch gateway configuration parameters.
    $gatewayParams = getGatewayVariables('payfast');

    define('PF_DEBUG', $gatewayParams['debug'] == 'on');

    $payfastCommon->pflog('Payfast capture called');

    // Payfast Configuration Parameters
    $merchantId = $params['merchant_id'];
    $passphrase = $params['passphrase'];
    $testMode   = $params['test_mode'];

    // Invoice Parameters
    $invoiceId = $params['invoiceid'];
    $amount    = $params['amount'];

    $guid = $params['gatewayid'];

    //Build URL
    $url = 'https://api.payfast.co.za/subscriptions/' . $guid . '/adhoc';

    if ($testMode == 'on') {
        $url = $url . '?testing=true';
        //Log testing true
        $payfastCommon->pflog("url: ?testing=true");

        //Use default sandbox credentials if no merchant id set
        if (empty($params['merchant_id'])) {
            $merchantId = '10004002';
            $passphrase = 'payfast';
        }
    }

    $hashArray = array();
    $payload   = array();

    $payload['amount']    = $amount * 100;
    $payload['item_name'] = $params['companyname'] . ' purchase, Invoice ID #' . $params['invoiceid'];

    //Prevention of race condition on adhoc ITN check
    $payload['item_description'] = 'tokenized-adhoc-payment-dc0521d355fe269bfa00b647310d760f';

    $payload['m_payment_id'] = $invoiceId;

    $hashArray['version']     = 'v1';
    $hashArray['merchant-id'] = $merchantId;
    $hashArray['passphrase']  = $passphrase;
    $hashArray['timestamp']   = date('Y-m-d') . 'T' . date('H:i:s');
    $orderedPrehash           = array_merge($hashArray, $payload);
    ksort($orderedPrehash);
    $signature = md5(http_build_query($orderedPrehash));

    //log Post data
    $payfastCommon->pflog('version: ' . $hashArray['version']);
    $payfastCommon->pflog('merchant-id: ' . $hashArray['merchant-id']);
    $payfastCommon->pflog('signature: ' . $signature);
    $payfastCommon->pflog('timestamp: ' . $hashArray['timestamp']);

    // configure curl
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'version: v1',
        'merchant-id: ' . $merchantId,
        'signature: ' . $signature,
        'timestamp: ' . $hashArray['timestamp'],
    ));

    $response = curl_exec($ch);
    //Log API response
    $payfastCommon->pflog('response :' . $response);

    curl_close($ch);

    $pfResponse = json_decode($response);

    // Close log
    $payfastCommon->pflog('', true);

    return array(
        // 'success' if successful, otherwise 'declined', 'error' for failure
        'status'  => (($pfResponse->data->response == 'true') ? 'success' : 'declined'),
        // Data to be recorded in the gateway log - can be a string or array
        'rawdata' => (empty($response) ? '' : $response),
        // Unique Transaction ID for the capture transaction
        'transid' => (($pfResponse->data->response == 'true') ? $pfResponse->data->pf_payment_id : ''),
        // Optional fee amount for the fee value refunded
        //'fees' => $feeAmount,
    );
}

/**
 * Cleanup tblhosting.
 *
 * Removes old tokens from tblhosting.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 **
 */
function cleanupTblHosting(array $params): void
{
    $userId   = $params['clientdetails']['userid'];
    $oldSubId = Illuminate\Database\Capsule\Manager::table('tblhosting')
                                                   ->where('userid', $userId)
                                                   ->where('subscriptionid', '<>', '')
                                                   ->latest('id')
                                                   ->first();
    $oldSubId = $oldSubId->subscriptionid;
    if (!empty($oldSubId)) {
        Illuminate\Database\Capsule\Manager::table('tblhosting')
                                           ->where('userid', $userId)
                                           ->update(
                                               [
                                                   'subscriptionid' => '',
                                               ]
                                           );
    }
}
