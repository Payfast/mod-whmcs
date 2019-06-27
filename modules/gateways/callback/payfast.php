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
define( 'SECONDS_TO_WAIT_FOR_ADHOC_RESPONSE', 5 );

// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

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

//// Notify PayFast that information has been received
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

//// Retrieve data returned in PayFast callback
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

//// Verify security signature
if ( !$pfError )
{
    pflog( 'Verify security signature' );

    if ( empty( $gatewayParams['passphrase'] ) )
    {
        $passphrase = null;
    }
    else
    {
        $passphrase = $gatewayParams['passphrase'];
    }

    // If signature different, log for debugging
    if ( !pfValidSignature( $pfData, $pfParamString, $passphrase ) )
    {
        $pfError = true;
        $pfErrMsg = PF_ERR_INVALID_SIGNATURE;
    }
}

//// Verify source IP
if ( !$pfError )
{
    pflog( 'Verify source IP' );

    if ( !pfValidIP( $_SERVER['REMOTE_ADDR'] ) )
    {
        $pfError = true;
        $pfErrMsg = PF_ERR_BAD_SOURCE_IP;
    }
}

// Get internal order and verify it hasn't already been processed
if ( !$pfError )
{
    pflog( "Check order hasn't been processed" );
    // Checks invoice ID is a valid invoice number or ends processing
    $whInvoiceID = checkCbInvoiceID( $invoiceId, $gatewayParams['name'] );
    //( ' this is the invoice id returned: ' . $whInvoiceID );
}

//// Verify data received
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

pflog( 'Check status and update order' );

$transactionStatus = 'Unsuccessful';

if ( $pfData['payment_status'] == "COMPLETE" && !$pfError )
{
    $transactionStatus = 'Successful';

    // Convert currency if necessary
    if ( $GATEWAY['convertto'] != '' && $pfData['custom_str2'] != 'ZAR' )
    {
        $currencies = Illuminate\Database\Capsule\Manager::table( 'tblcurrencies' )
            ->where( 'code', $pfData['custom_str1'] )
            ->get();

        $amountGross = convertCurrency( $pfData['amount_gross'], $GATEWAY['convertto'], $currencies[0]->id );
        $amountFee = convertCurrency( $pfData['amount_fee'], $GATEWAY['convertto'], $currencies[0]->id );

        pflog( 'amountGross: ' . $amountGross );
        pflog( 'amountFee: ' . $amountFee );
        pflog( 'convertto: ' . $GATEWAY['convertto'] );
        pflog( 'currency: ' . $currencies[0]->id );
    }
    else
    {
        $amountGross = $pfData['amount_gross'];
        $amountFee = $pfData['amount_fee'];
    }

    //Check if response is adhoc
    if ( $pfData['item_description'] == 'adhoc payment dc0521d355fe269bfa00b647310d760f' )
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
            Illuminate\Database\Capsule\Manager::table( 'tblaccounts' )
                ->where( 'amountin', $pfData['amount_gross'] )
                ->where( 'invoiceid', $invoiceId )
                ->update(
                    [
                        'fees' => abs( $pfData['amount_fee'] ),
                    ]
                );
            pflog( "Fees updated" );
            // Close log
            pflog( '', true );
            return;
        }
        return;
    }

    // Checks transaction number isn't already in the database and ends processing if it does
    checkCbTransID( $pfData['m_payment_id'] );

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
        $transactionId = $pfData['m_payment_id'],
        $paymentAmount = $amountGross,
        $paymentFee = -1 * $amountFee,
        $gatewayModuleName
    );

    //Select user id
    $user_id = Illuminate\Database\Capsule\Manager::table( 'tblinvoices' )
        ->where( 'id', $invoiceId )
        ->value( 'userid' );

    //Check if user has a token
    $pf_token = Illuminate\Database\Capsule\Manager::table( 'tblclients' )
        ->where( 'id', $user_id )
        ->value( 'gatewayid' );

    //Add token to db on adhoc subscription
    if ( !empty( $pfData['token'] ) && empty( $pf_token ) )
    {
        //Add tokenized Credit Card information.
        $add_token = json_encode( Illuminate\Database\Capsule\Manager::table( 'tblclients' )
                ->where( 'id', $user_id )
                ->update(
                    [
                        'gatewayid' => $pfData['token'],
                        'cardtype' => 'cc',
                        //'cardlastfour' => $pfData['custom_str4'],
                    ]
                ) );

        pflog( "Add new token : " . ( !empty( $add_token ) ? 'success' : 'failed' ) );

    }
}

logTransaction( $gatewayParams['name'], $_POST, $transactionStatus );

// Close log
pflog( '', true );
