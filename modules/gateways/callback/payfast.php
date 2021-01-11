<?php
/**
 *
 * PayFast Callback File
 *
 * Copyright (c) 2008 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 *
 */

//Prevention of race condition
//PayFast waits up to 20 seconds for a response
define( 'SECONDS_TO_WAIT_FOR_ADHOC_RESPONSE', 10 );

// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

// getCCVariables is required for email to clients within addInvoicePayment call
 if (!function_exists('getCCVariables')) {
     require_once __DIR__ . '/../../../includes/ccfunctions.php';
 }

App::load_function('gateway');
App::load_function('invoice');
// Detect module name from filename.
$gatewayModuleName = basename( __FILE__, '.php' );

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables( $gatewayModuleName );

require_once __DIR__ . '/../payfast/payfast_common.inc';

// Variable Initialization
$pfError = false;
$pfErrMsg = '';
$pfData = array();
$pfHost = (  ( $gatewayParams['test_mode'] == 'on' ) ? 'sandbox' : 'www' ) . '.payfast.co.za';
$pfOrderId = '';
$pfParamString = '';

pflog( 'PayFast ITN call received' );

// Notify PayFast that information has been received
if ( !$pfError )
{
    header( 'HTTP/1.0 200 OK' );
    flush();
}

// Die if module is not active.
if ( !$gatewayParams['type'] )
{
    die( "Module Not Activated" );
}

// Retrieve data returned in PayFast callback
if ( !$pfError )
{
    pflog( 'Get posted data' );

    // Posted variables from ITN
    $pfData = pfGetData();

    pflog( 'PayFast Data: ' . print_r( $pfData, true ) );
    //logActivity( 'PayFast Data: '. print_r( $pfData, true ) );

    if ( $pfData === false )
    {
        $pfError = true;
        $pfErrMsg = PF_ERR_BAD_ACCESS;
    }
}

/**
 * Validate callback authenticity.
 *
 */

$invoiceId = substr( $pfData['item_name'], strpos( $pfData['item_name'], '#' ) + 1 );

// Verify security signature
if ( !$pfError )
{
    pflog( 'Verify security signature' );

    $passphrase = null;

    if ( !empty( $gatewayParams['passphrase'] ) )
    {
        $passphrase = $gatewayParams['passphrase'];
    }

    if($gatewayParams['test_mode'] == 'on' && empty( $gatewayParams['merchant_id'] ) )
    {
        $passphrase = 'payfast';
    }

    // If signature different, log for debugging
    if ( !pfValidSignature( $pfData, $pfParamString, $passphrase ) )
    {
        $pfError = true;
        $pfErrMsg = PF_ERR_INVALID_SIGNATURE;
    }
}

// This check has been removed due to the increasing popularity of cloud hosting.
// Verify source IP is PayFast
// if ( !$pfError )
// {
//     pflog( 'Verify source IP' );

//     if ( !pfValidIP( $_SERVER['REMOTE_ADDR'] ) )
//     {
//         $pfError = true;
//         $pfErrMsg = PF_ERR_BAD_SOURCE_IP;
//     }
// }

// Get internal order and verify it hasn't already been processed
if ( !$pfError )
{
    pflog( "Check order hasn't been processed" );
    // Checks invoice ID is a valid invoice number or ends processing
    $whInvoiceID = checkCbInvoiceID( $invoiceId, $gatewayParams['name'] );
    //( ' this is the invoice id returned: ' . $whInvoiceID );
}

// Verify data received
if ( !$pfError )
{
    pflog( 'Verify data received' );

    $pfValid = pfValidData( $pfHost, $pfParamString );

    if ( !$pfValid )
    {
        $pfError = true;
        $pfErrMsg = PF_ERR_BAD_ACCESS;
    }
}

$transactionStatus = 'Unsuccessful';

if ( $pfData['payment_status'] == "COMPLETE" && !$pfError )
{
    pflog( 'Checking order' );
    $transactionStatus = 'Successful';

    // Convert currency if necessary
    if ( $gatewayParams['convertto'] != '' && $pfData['custom_str2'] != 'ZAR' )
    {
        pflog( 'Converting currency' );
        $currencies = Illuminate\Database\Capsule\Manager::table( 'tblcurrencies' )
            ->where( 'code', $pfData['custom_str2'] )
            ->get();

        $amountGross = convertCurrency( $pfData['amount_gross'], $gatewayParams['convertto'], $currencies[0]->id );
        $amountFee = convertCurrency( $pfData['amount_fee'], $gatewayParams['convertto'], $currencies[0]->id );

        pflog( 'amountGross: ' . $amountGross );
        pflog( 'amountFee: ' . $amountFee );
        pflog( 'convertto: ' . $gatewayParams['convertto'] );
        pflog( 'currency: ' . $currencies[0]->id );
    }
    else
    {
        $amountGross = $pfData['amount_gross'];
        $amountFee = $pfData['amount_fee'];
    }

    //Check if response is adhoc
    if ( $pfData['item_description'] == 'tokenized-adhoc-payment-dc0521d355fe269bfa00b647310d760f' )
    {
        pflog( "adhoc payment" );

        //Check invoice status
        $invStatus = Illuminate\Database\Capsule\Manager::table( 'tblinvoices' )
            ->where( 'id', $invoiceId )
            ->value( 'status' );
        pflog( "Invoice Status " . $invStatus );

        //Prevention of race condition
        if ( $invStatus != 'Paid' )
        {
            pflog( "Waiting " . SECONDS_TO_WAIT_FOR_ADHOC_RESPONSE . " seconds for adhoc response " );
            sleep( SECONDS_TO_WAIT_FOR_ADHOC_RESPONSE );
            $invStatus = Illuminate\Database\Capsule\Manager::table( 'tblinvoices' )
                ->where( 'id', $invoiceId )
                ->value( 'status' );
            pflog( "Invoice Status " . $invStatus );
        }

        if ( $invStatus == 'Paid' )
        {
            pflog( "Updating adhoc payment fees" );
            Illuminate\Database\Capsule\Manager::table( 'tblaccounts' )
                ->where( 'amountin', $pfData['amount_gross'] )
                ->where( 'invoiceid', $invoiceId )
                ->update(
                    [
                        'fees' => abs( $pfData['amount_fee'] ),
                    ]
                );
            // Close log
            pflog( '', true );
        }
        return;
    }

    // Checks transaction number isn't already in the database and ends processing if it does
    checkCbTransID( $pfData['pf_payment_id'] );

    /**
     * Add Invoice Payment.
     *
     * Applies a payment transaction entry to the given invoice ID.
     *
     * @param int $invoiceId         Invoice ID
     * @param string $transactionId  Transaction ID
     * @param float $paymentAmount   Amount paid (defaults to full balance)
     * @param float $paymentFee      Payment fee (optional)
     * @param string $gatewayModule  Gateway module name
     */
    addInvoicePayment(
        $invoiceId,
        $transactionId = $pfData['pf_payment_id'],
        $paymentAmount = $amountGross,
        $paymentFee = -1 * $amountFee,
        $gatewayModuleName
    );

    //Select user id
    $user_id = Illuminate\Database\Capsule\Manager::table( 'tblinvoices' )
        ->where( 'id', $invoiceId )
        ->value( 'userid' );

    //Add token on adhoc subscription
    if ( !empty( $pfData['token'] ) && !empty($pfData['custom_str1']) )
    {

        $token_added = false;

        //Backwards compatibility check
        if( preg_match('/PF_WHMCS_(.*?)\.\d_/', $pfData['custom_str1'], $whmcs_ver) == 1
            && !( floatval($whmcs_ver[1]) >= 7.9 ))
        {

            pflog( "Manually store token in database for backwards compatibility with WHMCS ".$whmcs_ver[1]);
            //Add token as tokenized Credit Card information on user profile.
            $add_token = json_encode( Illuminate\Database\Capsule\Manager::table( 'tblclients' )
                    ->where( 'id', $user_id )
                    ->update(
                        [
                            'gatewayid' => $pfData['token'],
                            'cardtype' => 'cc',
                            //'cardlastfour' => $pfData['custom_str4'],
                        ]
                    ) );
                    $token_added = !empty( $add_token );

        }else{

            pflog( "Store adhoc token as tokenized PayFast Pay Method" );
            //Add token as tokenized Credit Card for PayFast payment method on user profile.
            try {
                // Function available in WHMCS 7.9 and later
                $token_added = createCardPayMethod (
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
            }catch (Exception $e) {
                // Log to gateway log as unsuccessful.
                pflog('Add new token failed : '.$e->getMessage());
                logTransaction($gatewayParams['paymentmethod'], $_REQUEST, $e->getMessage());
                // Show failure message.
                echo 'Add new token failed :'.$e;
            }
        }

        pflog( "Add new token : " . ( $token_added ? 'success' : 'failed' ) );

        }
    }

/**
 * Log Transaction.
 *
 * Add an entry to the PayFast Log for debugging purposes.
 *
 * Check status and update order
 *
 * @param string $gatewayName        Display label
 * @param string|array $debugData    Data to log
 * @param string $transactionStatus  Status
 */
logTransaction( $gatewayParams['name'], $_POST, $transactionStatus );

// If an error occurred
if ($pfError) {
    pflog('Error occurred: ' . $pfErrMsg);
}

// Close log
pflog( '', true );
