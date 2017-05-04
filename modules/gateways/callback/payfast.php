<?php
/**
 * payfast.php
 *
 * PayFast ITN handler
 *
 * Copyright (c) 2008 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 *
 * @author     Jonathan Smit
 * @link       http://www.payfast.co.za/help/whmcs
 */

# Required File Includes
include( "../../../init.php" );
//include( "../../../dbconnect.php" );
include( "../../../includes/functions.php" );
include( "../../../includes/gatewayfunctions.php" );
include( "../../../includes/invoicefunctions.php" );

// Get WHMCS PayFast module variables
$gatewaymodule = 'payfast';
$GATEWAY = getGatewayVariables( $gatewaymodule );

// Check gateway module is active before accepting callback
if( !$GATEWAY['type'] )
    die( 'Module Not Activated' );

// Include the PayFast common file
define( 'PF_DEBUG', ( $GATEWAY['debug'] == 'on' ? true : false ) );
require_once('../payfast/payfast_common.inc');
require_once( "../../../modules/gateways/payfast/v6_include.php" );

//logActivity( 'PayFast Itn Received' );

// Variable Initialization
$pfError = false;
$pfErrMsg = '';
$pfData = array();
$pfHost = ( ( $GATEWAY['test_mode'] == 'on' ) ? 'sandbox' : 'www' ) . '.payfast.co.za';
$pfOrderId = '';
$pfParamString = '';

pflog( 'PayFast ITN call received' );

//// Notify PayFast that information has been received
if( !$pfError )
{
    header( 'HTTP/1.0 200 OK' );
    flush();
}

//// Get data sent by PayFast
if( !$pfError )
{
    pflog( 'Get posted data' );

    // Posted variables from ITN
    $pfData = pfGetData();

    pflog( 'PayFast Data: '. print_r( $pfData, true ) );
    //logActivity( 'PayFast Data: '. print_r( $pfData, true ) );

    if( $pfData === false )
    {
        $pfError = true;
        $pfErrMsg = PF_ERR_BAD_ACCESS;
    }
}

//// Verify security signature
if( !$pfError )
{
    pflog( 'Verify security signature' );

    if ( $GATEWAY['test_mode'] == 'on' )
    {
        $passphrase = 'payfast';
    }
    elseif ( empty( $GATEWAY['passphrase'] ) && $GATEWAY['test_mode'] != 'on' )
    {
        $passphrase = null;
    }
    else
    {
        $passphrase = $GATEWAY['passphrase'];
    }

    // If signature different, log for debugging
    if( !pfValidSignature( $pfData, $pfParamString, $passphrase ) )
    {
        $pfError = true;
        $pfErrMsg = PF_ERR_INVALID_SIGNATURE;
    }
}

//// Verify source IP (If not in debug mode)
if( !$pfError && !defined( 'PF_DEBUG' ) )
{
    pflog( 'Verify source IP' );

    if( !pfValidIP( $_SERVER['REMOTE_ADDR'] ) )
    {
        $pfError = true;
        $pfErrMsg = PF_ERR_BAD_SOURCE_IP;
    }
}

//// Get internal order and verify it hasn't already been processed
if( !$pfError )
{
    pflog( "Check order hasn't been processed" );

    $invId = is_numeric( $pfData['item_description'] ) ? $pfData['item_description'] : $pfData['m_payment_id'];

    // Checks invoice ID is a valid invoice number or ends processing
    $whInvoiceID = checkCbInvoiceID( $invId, $GATEWAY['name'] );
    //( ' this is the invoice id returned: ' . $whInvoiceID );
    // Checks transaction number isn't already in the database and ends processing if it does
    if ( $pfData['payment_status'] == 'COMPLETE' )
    {
        checkCbTransID( $pfData['pf_payment_id'] );
    }
}

//// Verify data received
if( !$pfError )
{
    pflog( 'Verify data received' );

    $pfValid = pfValidData( $pfHost, $pfParamString );

    if( !$pfValid )
    {
        $pfError = true;
        $pfErrMsg = PF_ERR_BAD_ACCESS;
    }
}

//// Check status and update order
if( !$pfError )
{
    //  require_once( 'payfast/' . getVer() );
    pflog( 'Check status and update order' );

    // Convert currency if necessary
    if ( $GATEWAY['convertto'] != '' && $pfData['custom_str1'] != 'ZAR' )
    {
        $currencies = Illuminate\Database\Capsule\Manager::table('tblcurrencies')
            ->where('code', $pfData['custom_str1'])
            ->get();

        $amountGross = convertCurrency( $pfData['amount_gross'], $GATEWAY['convertto'], $currencies[0]->id );
        $amountFee = convertCurrency( $pfData['amount_fee'], $GATEWAY['convertto'], $currencies[0]->id );
    }
    else
    {
        $amountGross = $pfData['amount_gross'];
        $amountFee = $pfData['amount_fee'];
    }

    if( $pfData['payment_status'] == "COMPLETE" )
    {
        // Successful
        addInvoicePayment( $whInvoiceID, $pfData['pf_payment_id'],
            $amountGross, -1 * $amountFee, $gatewaymodule );
        logTransaction( $GATEWAY['name'], $_POST, 'Successful' );

        if ( !empty( $pfData['token'] ) )
        {
            setSubscriptionId( $pfData['token'], $pfData['custom_str2'] );
            setDomainStatus( $pfData['custom_str2'] );
        }
    }
    elseif ( $pfData['payment_status'] == 'CANCELLED' )
    {
        setTblHostingCancelStatus( $pfData['custom_str2'] );
        setTblOrdersCancelStatus( $pfData['m_payment_id'] );
    }
    else
    {
        // Unsuccessful
        logTransaction( $GATEWAY['name'], $_POST, 'Unsuccessful' );

    }
}

// If an error occurred
if( $pfError )
{
    pflog( 'Error occurred: '. $pfErrMsg );
    pflog( 'Sending email notification' );

    // Send an email
    $subject = "PayFast ITN error: ". $pfErrMsg;
    $body =
        "Hi,\n\n".
        "An invalid PayFast transaction on your website requires attention\n".
        "------------------------------------------------------------\n".
        "Site: ". $CONFIG['CompanyName'] ."\n".
        "Remote IP Address: ". $_SERVER['REMOTE_ADDR'] ."\n".
        "Remote host name: ". gethostbyaddr( $_SERVER['REMOTE_ADDR'] ) ."\n";
    if( isset( $pfData['pf_payment_id'] ) )
        $body .= "Order ID: ". $pfData['m_payment_id'] ."\n";
    if( isset( $pfData['pf_payment_id'] ) )
        $body .= "PayFast Transaction ID: ". $pfData['pf_payment_id'] ."\n";
    if( isset( $pfData['payment_status'] ) )
        $body .= "PayFast Payment Status: ". $pfData['payment_status'] ."\n";
    $body .=
        "\nError: ". $pfErrMsg ."\n";

    $mail = new PHPMailer();
    $mail->AddAddress( $CONFIG['Email'], '' );
    $mail->Subject = $subject;
    $mail->IsHTML( false );
    $mail->Body = $body;

    if( !$mail->Send())
        pflog( 'Mailer Error: '. $mail->ErrorInfo );
    else
        pflog( 'Message sent!' );
}

// Close log
pflog( '', true );
?>