<?php
/**
 * Copyright (c) 2008 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 */

add_hook( "DailyCronJob", 1, "payfastchargesubscription" );

function payfastchargesubscription()
{
    $gatewaymodule = 'payfast';
    $GATEWAY = getGatewayVariables( $gatewaymodule );

    if ( $GATEWAY['test_mode'] == 'on' )
    {
        $merchantId = '10004002';
        $passphrase = 'payfast';
        $uri = '/adhoc?testing=true';
    }
    else
    {
        $merchantId = $GATEWAY['merchant_id'];
        $passphrase = $GATEWAY['passphrase'];
        $uri = '/adhoc';
    }

    $subscriptions = Illuminate\Database\Capsule\Manager::table('tblhosting')
        ->get();

    foreach ( $subscriptions as $subscription )
    {
        $userId = $subscription->userid;
        $clientRec = Illuminate\Database\Capsule\Manager::table('tblclients')
            ->where('id', $userId )
            ->get();
        $subsToken = $clientRec[0]->gatewayid;

        $oldSubId = $subscription->subscriptionid;

        if ( !empty( $subsToken ) )
        {
            $clientSubId = $subsToken;
        }
        else
        {
            $clientSubId = $oldSubId;
        }

        if ( $subscription->paymentmethod == 'payfast' && $subscription->nextduedate == gmdate( 'Y-m-d' )
            && !empty( $clientSubId ) && $subscription->domainstatus == 'Active'
            && $subscription->amount > 0 )
        {
            $invoiceHostingItems = Illuminate\Database\Capsule\Manager::table('tblinvoiceitems')
                ->where('relid', $subscription->id)
                ->where('duedate', $subscription->nextduedate)
                ->get();

            $hashArray = array();
            $payload = array();

            $payload['amount'] = $subscription->amount * 100;
            $payload['item_name'] = 'Invoice ID #' . $invoiceHostingItems[0]->invoiceid;
            $payload['item_description'] = 'Order ID #' . $subscription->orderid;
            $guid = $clientSubId;

            $hashArray['version'] = 'v1';
            $hashArray['merchant-id'] = $merchantId;
            $hashArray['passphrase'] = $passphrase;
            $hashArray['timestamp'] = date('Y-m-d') . 'T' . date('H:i:s');
            $orderedPrehash = array_merge( $hashArray, $payload );
            ksort( $orderedPrehash );
            $signature = md5( http_build_query( $orderedPrehash ) );
            $domain = "https://api.payfast.co.za";
            // Configure cURL
            $url = $domain . '/subscriptions/' . $guid . $uri;
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

            $pfResponse = json_decode( $response );

            if ( $pfResponse->data->response == true )
            {
                logActivity( 'PayFast subscription payment with subscriptionid: ' . $guid . ' successfully completed' );
            }
            else
            {
                logActivity( 'PayFast subscription payment with subscriptionid: ' . $guid . ' failed' );
            }
        }
    }
}