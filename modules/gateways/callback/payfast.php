<?php
/**
 * payfast.php
 *
 * PayFast ITN handler
 *
 * Copyright (c) 2010-2011 PayFast (Pty) Ltd
 *
 * LICENSE:
 *
 * This payment module is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation; either version 3 of the License, or (at
 * your option) any later version.
 *
 * This payment module is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public
 * License for more details.
 *
 * @author     Jonathan Smit
 * @copyright  2010-2011 PayFast (Pty) Ltd
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
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

    if( $pfData['payment_status'] == "COMPLETE" )
    {
        // Successful
        addInvoicePayment( $whInvoiceID, $pfData['pf_payment_id'],
            $pfData['amount_gross'], -1 * $pfData['amount_fee'], $gatewaymodule );
        logTransaction( $GATEWAY['name'], $_POST, 'Successful' );

        if ( !empty( $pfData['token'] ) )
        {
            setSubscriptionId( $pfData['token'], $pfData['custom_int1'] );
            setDomainStatus( $pfData['custom_int1'] );
        }
    }
    elseif ( $pfData['payment_status'] == 'CANCELLED' )
    {
        setTblHostingCancelStatus( $pfData['custom_int1'] );
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