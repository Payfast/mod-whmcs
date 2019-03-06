<?php
/**
 * Copyright (c) 2008 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 *
 */

if ( !empty( $_POST['subscriptionid'] )  )
{
    include( "../../../init.php" );
    include( "../../../includes/functions.php" );
    include( "../../../includes/gatewayfunctions.php" );
    include( "../../../includes/invoicefunctions.php" );

    $gatewaymodule = 'payfast';
    $GATEWAY = getGatewayVariables( $gatewaymodule );
    $guid = $_POST['subscriptionid'];

    if ( $GATEWAY['test_mode'] == 'on' )
    {
        $merchantId = '10004002';
        $passphrase = 'payfast';
        $url = 'https://api.payfast.co.za/subscriptions/' . $guid .'/adhoc?testing=true';
    }
    else
    {
        $merchantId = $GATEWAY['merchant_id'];
        $passphrase = $GATEWAY['passphrase'];
        $url = 'https://api.payfast.co.za/subscriptions/' . $guid .'/adhoc';
    }

    $hashArray = array();
    $payload = array();

    $payload['amount'] = $_POST['amount'] *100;
    $payload['item_name'] = $_POST['item_name'];
    $payload['item_description'] = $_POST['item_description'];
    $guid = $_POST['subscriptionid'];

    $hashArray['version'] = 'v1';
    $hashArray['merchant-id'] = $merchantId;
    $hashArray['passphrase'] = $passphrase;
    $hashArray['timestamp'] = date('Y-m-d') . 'T' . date('H:i:s');
    $orderedPrehash = array_merge( $hashArray, $payload );
    ksort( $orderedPrehash );
    $signature = md5( http_build_query( $orderedPrehash ) );
    // Configure cURL
    $ch = curl_init($url);
    $useragent = 'WHMCS';
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'version: v1',
        'merchant-id: ' . $merchantId,
        'signature: ' . $signature,
        'timestamp: ' . $hashArray['timestamp']
    ));
    $response = curl_exec( $ch );
    curl_close( $ch );

    // sleep( 5 );

    $pfResponse = json_decode( $response );

    $whInvoiceID = substr( $_POST['item_name'], strpos( $_POST['item_name'], '#' )  + 1 );

    if ( $pfResponse->data->message == true )
    {
        //    sleep( 5 );
        logActivity( 'PayFast Ad Hoc payment with subscriptionid: ' . $guid . ' successfully completed' );
        header("Location:" . $params['systemurl'] . '/viewinvoice.php?id=' . $whInvoiceID);
    }
    else
    {
        logActivity( 'PayFast Ad Hoc payment with subscriptionid: ' . $guid . ' failed with message: ' . $pfResponse->data->message );
        header("Location:" . $params['systemurl'] . '/viewinvoice.php?id=' . $whInvoiceID);
    }
}