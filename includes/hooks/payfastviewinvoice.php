<?php
/**
 * Copyright (c) 2008 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 */

add_hook( "ClientAreaPageViewInvoice", 1, "oneClickPayment" );

function oneClickPayment($params)
{
    $gatewaymodule = 'payfast';
    $GATEWAY = getGatewayVariables( $gatewaymodule );

    if ( $params['status'] != 'Paid' )
    {
        $clientSubId = getSubscriptionId($params['clientsdetails']['userid']);
    }

    $subscription = Illuminate\Database\Capsule\Manager::table('tblhosting')
        ->where('id', $params['invoiceitems'][0]['relid'])
        ->get();

    $subscriptionId = $subscription[0]->subscriptionid;
    $paymentMethod = $subscription[0]->paymentmethod;
    $orderId = $subscription[0]->orderid;

    if ( substr( $params['systemurl'], -1 ) == '/' )
    {
        $params['systemurl'] = substr_replace( $params['systemurl'], '', -1 );
    }

    if ( ( $GATEWAY['enable_single_token'] && !empty( $clientSubId['subscriptionid'] ) ) || ( $paymentMethod == 'payfast' && !empty( $subscriptionId ) && $params['status'] != 'Paid' /*&& empty( $_POST['makeadhocpayment'] )*/ ) )
    {
        $subscriptionId = !empty( $clientSubId['subscriptionid'] ) ? $clientSubId['subscriptionid'] : $subscriptionId;
        ?>
        <div class="col-sm-12 text-center" style="margin-top: 20px"><form id="payfast_form" name="payfast_form" action="viewinvoice.php?id=<?php echo $params['invoiceid'] ?>" method="post" onsubmit="return loader();">
                <input type="hidden" name="makeadhocpayment" value="makeadhocpayment">
                <input type="image" src="<?php echo $params['systemurl'] ?>/modules/gateways/payfast/images/light-small-paynow.png" value="Pay Now" id="paynow" />
            </form></div>

        <script type="text/javascript">

            function loader(){
                var loader = document.getElementById("loader"),

                    show = function(){
                        loader.style.display = "block";
                        setTimeout(hide, 8000);
                        document.getElementById("paynow").disabled = true;
                    },

                    hide = function(){
                        loader.style.display = "none";
                    };

                show();
            };

        </script>

        <div id="loader" class="col-sm-12 text-center" style="display:none"><img id = "myImage" src = "<?php echo $params['systemurl']  ?>/modules/gateways/payfast/images/loading.gif"></div>

        <?php
    }

    if ( !empty( $_POST['makeadhocpayment'] ) && $params['status'] != 'Paid' )
    {
        $guid = $subscriptionId;

        // Get total
        $total = 0;
        $count = count( $params['invoiceitems'] );

        for ($i = 0; $i <= $count; $i++)
        {
            $total += $params['invoiceitems'][$i]['rawamount'];
        }

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

        $payload['amount'] = $total * 100;
        $payload['item_name'] = $params['companyname'] .' purchase, Invoice ID #'. $params['invoiceid'];
        $payload['item_description'] = $params['companyname'] .' purchase, Order ID #'. $orderId;
        $guid = $subscriptionId;

        $hashArray['version'] = 'v1';
        $hashArray['merchant-id'] = $merchantId;
        $hashArray['passphrase'] = $passphrase;
        $hashArray['timestamp'] = date('Y-m-d') . 'T' . date('H:i:s');
        $orderedPrehash = array_merge( $hashArray, $payload );
        ksort( $orderedPrehash );
        $signature = md5( http_build_query( $orderedPrehash ) );
        // configure curl
        $ch = curl_init($url);
        $useragent = 'WHMCS';
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query($payload));
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

        if ( $pfResponse->data->message == 'Success' )
        {
            //    sleep( 5 );
            logActivity( 'PayFast Ad Hoc payment with subscriptionid: ' . $guid . ' successfully completed' );
            header("Location:" . $params['systemurl'] . '/viewinvoice.php?id=' . $params['invoiceid']);
        }
        else
        {
            ?><script type="text/javascript"> alert('An error occured whilst attempting to make the payment, please try again')</script><?php
            logActivity( 'PayFast Ad Hoc payment with subscriptionid: ' . $guid . ' failed with message: ' . $pfResponse->data->message );
        }
    }
}
