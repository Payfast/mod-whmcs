<?php
/**
 *
 * Payfast Callback File
 *
 * Copyright (c) 2024 Payfast (Pty) Ltd
 * You (being anyone who is not Payfast (Pty) Ltd) may download and use this plugin /
 * code in your own website in conjunction with a registered and active Payfast account.
 * If your Payfast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin /
 * code or part thereof in any way.
 *
 */

// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

require_once __DIR__ . '/../payfast/vendor/autoload.php';

use Payfast\PayfastCommon\PayfastCommon;

// getCCVariables is required for email to clients within addInvoicePayment call
if (!function_exists('getCCVariables')) {
    require_once __DIR__ . '/../../../includes/ccfunctions.php';
}

App::load_function('gateway');
App::load_function('invoice');
// Detect module name from filename.
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);

define('PF_DEBUG', $gatewayParams['debug'] == 'on');

// Variable Initialization
$pfError       = false;
$pfErrMsg      = '';
$pfData        = array();
$pfHost        = (($gatewayParams['test_mode'] == 'on') ? 'sandbox' : 'www') . '.payfast.co.za';
$pfOrderId     = '';
$pfParamString = '';

PayfastCommon::pflog('Payfast ITN call received');

// Notify Payfast that information has been received
header('HTTP/1.0 200 OK');
flush();

// Die if module is not active.
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

// Retrieve data returned in Payfast callback
PayfastCommon::pflog('Get posted data');

// Posted variables from ITN
$pfData = PayfastCommon::pfGetData();

PayfastCommon::pflog('Payfast Data: ' . print_r($pfData, true));

if ($pfData === false) {
    $pfError  = true;
    $pfErrMsg = PayfastCommon::PF_ERR_BAD_ACCESS;
}

/**
 * Validate callback authenticity.
 *
 */

$invoiceId = substr($pfData['item_name'], strpos($pfData['item_name'], '#') + 1);

// Verify security signature
if (!$pfError) {
    PayfastCommon::pflog('Verify security signature');

    $passphrase = null;

    if (!empty($gatewayParams['passphrase'])) {
        $passphrase = $gatewayParams['passphrase'];
    }

    if ($gatewayParams['test_mode'] == 'on' && empty($gatewayParams['merchant_id'])) {
        $passphrase = 'payfast';
    }

    // If signature different, log for debugging
    if (!PayfastCommon::pfValidSignature($pfData, $pfParamString, $passphrase)) {
        $pfError  = true;
        $pfErrMsg = PayfastCommon::PF_ERR_INVALID_SIGNATURE;
    }
}

// Get internal order and verify it hasn't already been processed
if (!$pfError) {
    PayfastCommon::pflog("Check order hasn't been processed");
    // Checks invoice ID is a valid invoice number or ends processing
    $whInvoiceID = checkCbInvoiceID($invoiceId, $gatewayParams['name']);
}

// Verify data received
if (!$pfError) {
    PayfastCommon::pflog('Verify data received');

    $pfValid = PayfastCommon::pfValidData($pfHost, $pfParamString);

    if (!$pfValid) {
        $pfError  = true;
        $pfErrMsg = PayfastCommon::PF_ERR_BAD_ACCESS;
    }
}

$transactionStatus = 'Unsuccessful';

if ($pfData['payment_status'] == "COMPLETE" && !$pfError) {
    PayfastCommon::pflog('Checking order');
    $transactionStatus = 'Successful';

    // Convert currency if necessary
    if ($gatewayParams['convertto'] != '' && $pfData['custom_str2'] != 'ZAR') {
        PayfastCommon::pflog('Converting currency');
        $currencies = Illuminate\Database\Capsule\Manager::table('tblcurrencies')
                                                         ->where('code', $pfData['custom_str2'])
                                                         ->get();

        $amountGross = convertCurrency($pfData['amount_gross'], $gatewayParams['convertto'], $currencies[0]->id);
        $amountFee   = convertCurrency($pfData['amount_fee'], $gatewayParams['convertto'], $currencies[0]->id);

        PayfastCommon::pflog('amountGross: ' . $amountGross);
        PayfastCommon::pflog('amountFee: ' . $amountFee);
        PayfastCommon::pflog('convertto: ' . $gatewayParams['convertto']);
        PayfastCommon::pflog('currency: ' . $currencies[0]->id);
    } else {
        $amountGross = $pfData['amount_gross'];
        $amountFee   = $pfData['amount_fee'];
    }

    //Check if response is adhoc
    if ($pfData['item_description'] == 'tokenized-adhoc-payment-dc0521d355fe269bfa00b647310d760f') {
        PayfastCommon::pflog("adhoc payment");

        //Check invoice status
        $invStatus = Illuminate\Database\Capsule\Manager::table('tblinvoices')
                                                        ->where('id', $invoiceId)
                                                        ->value('status');
        PayfastCommon::pflog("Invoice Status " . $invStatus);

        //Prevention of race condition
        if ($invStatus != 'Paid') {

            $adhocWaitTime = (int)$gatewayParams['adhoc_timer'];

            PayfastCommon::pflog("Waiting " . $adhocWaitTime . " seconds for adhoc response ");
            sleep($adhocWaitTime);
            $invStatus = Illuminate\Database\Capsule\Manager::table('tblinvoices')
                                                            ->where('id', $invoiceId)
                                                            ->value('status');
            PayfastCommon::pflog("Invoice Status " . $invStatus);
        }

        if ($invStatus == 'Paid') {
            PayfastCommon::pflog("Updating adhoc payment fees");
            Illuminate\Database\Capsule\Manager::table('tblaccounts')
                                               ->where('amountin', $pfData['amount_gross'])
                                               ->where('invoiceid', $invoiceId)
                                               ->update(
                                                   [
                                                       'fees' => abs($pfData['amount_fee']),
                                                   ]
                                               );
            // Close log
            PayfastCommon::pflog('', true);
        }

        return;
    }

    // Checks transaction number isn't already in the database and ends processing if it does
    checkCbTransID($pfData['pf_payment_id']);

    /**
     * Add Invoice Payment.
     *
     * Applies a payment transaction entry to the given invoice ID.
     *
     * @param int $invoiceId Invoice ID
     * @param string $transactionId Transaction ID
     * @param float $paymentAmount Amount paid (defaults to full balance)
     * @param float $paymentFee Payment fee (optional)
     * @param string $gatewayModule Gateway module name
     */
    addInvoicePayment(
        $invoiceId,
        $transactionId = $pfData['pf_payment_id'],
        $paymentAmount = $amountGross,
        $paymentFee = -1 * $amountFee,
        $gatewayModuleName
    );

    //Select user id
    $user_id = Illuminate\Database\Capsule\Manager::table('tblinvoices')
                                                  ->where('id', $invoiceId)
                                                  ->value('userid');

    //Add token on adhoc subscription
    if (!empty($pfData['token']) && !empty($pfData['custom_str1'])) {
        $token_added = false;

        //Backwards compatibility check
        if (preg_match('/PF_WHMCS_(.*?)\.\d_/', $pfData['custom_str1'], $whmcs_ver) == 1
            && (floatval($whmcs_ver[1]) < 7.9)) {
            PayfastCommon::pflog(
                "Manually store token in database for backwards compatibility with WHMCS "
                . $whmcs_ver[1]
            );
            //Add token as tokenized Credit Card information on user profile.
            $add_token   = json_encode(
                Illuminate\Database\Capsule\Manager::table('tblclients')
                                                   ->where('id', $user_id)
                                                   ->update(
                                                       [
                                                           'gatewayid' => $pfData['token'],
                                                           'cardtype'  => 'cc',
                                                           //'cardlastfour' => $pfData['custom_str4'],
                                                       ]
                                                   )
            );
            $token_added = !empty($add_token);
        } else {
            PayfastCommon::pflog("Store adhoc token as tokenized Payfast Pay Method");
            //Add token as tokenized Credit Card for Payfast payment method on user profile.
            try {
                // Function available in WHMCS 7.9 and later
                $token_added = createCardPayMethod(
                    $clientId = $user_id,
                    $gatewayName = $gatewayModuleName,
                    $cardNumber = "1234",
                    $cardExpiryDate = date('my', strtotime('+20 years')), //mmyy
                    $cardType = "cc",
                    $cardStartDate = null,
                    $cardIssueNumber = null,
                    $remoteToken = $pfData['token'],
                    $billingContactId = "billing",
                    $description = "Tokenization"
                );
            } catch (Exception $e) {
                // Log to gateway log as unsuccessful.
                PayfastCommon::pflog('Add new token failed : ' . $e->getMessage());
                logTransaction($gatewayParams['paymentmethod'], $_REQUEST, $e->getMessage());
                // Show failure message.
                echo 'Add new token failed :' . $e;
            }
        }

        PayfastCommon::pflog("Add new token : " . ($token_added ? 'success' : 'failed'));
    }
}

/**
 * Log Transaction.
 *
 * Add an entry to the Payfast Log for debugging purposes.
 *
 * Check status and update order
 *
 * @param string $gatewayName Display label
 * @param string|array $debugData Data to log
 * @param string $transactionStatus Status
 */
logTransaction($gatewayParams['name'], $_POST, $transactionStatus);

// If an error occurred
if ($pfError) {
    PayfastCommon::pflog('Error occurred: ' . $pfErrMsg);
}

// Close log
PayfastCommon::pflog('', true);
