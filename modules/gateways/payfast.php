<?php
/**
 * payfast.php
 *
 * Copyright (c) 2008 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 *
 * @author     Jonathan Smit
 * @link       http://www.payfast.co.za/help/whmcs
 */



// {{{ payfast_config()
/**
 * payfast_config()
 *
 * @return
 */
function payfast_config()
{
    $configArray = array(
        'FriendlyName' => array( 'Type' => 'System', 'Value' => 'PayFast' ),

        'merchant_id' => array( 'FriendlyName' => 'Merchant ID', 'Type' => 'text', 'Size' => '20',
            'Description' => 'Your Merchant ID as given on the <a href="http://www.payfast.co.za/acc/integration">Integration</a> page on PayFast', ),
        'merchant_key'  => array( 'FriendlyName' => 'Merchant Key', 'Type' => 'text', 'Size' => '20',
            'Description' => 'Your Merchant Key as given on the <a href="http://www.payfast.co.za/acc/integration">Integration</a> page on PayFast', ),
        'passphrase'  => array( 'FriendlyName' => 'PassPhrase', 'Type' => 'text', 'Size' => '32',
            'Description' => '*** DO NOT SET THIS UNLESS YOU HAVE SET IT ON THE <a href="http://www.payfast.co.za/acc/integration">Integration</a> PAGE ON PayFast ***', ),
        'enable_recurring' => array( 'FriendlyName' => 'Enable Recurring Billing', 'Type' => 'yesno', 'Description' => '*** You need to enable Ad Hoc Payments on the <a href="http://www.payfast.co.za/acc/integration">Integration</a> page on PayFast ***'),
        'force_recurring' => array( 'FriendlyName' => 'Force Recurring Billing', 'Type' => 'yesno', 'Description' => 'Hide the one time payment when a subscription can be created'),
        'enable_single_token' => array( 'FriendlyName' => 'Enable Single Subscription ID ', 'Type' => 'yesno', 'Description' => 'Set a single subscription ID for each client so they will not have to be redirected to PayFast for any payment on any invoice subsequent to the initial payment. Recurring billing must be enabled for this feature.'),
        'test_mode' => array( 'FriendlyName' => 'Test Mode', 'Type' => 'yesno',
            'Description' => 'Check this to put the interface in test mode', ),
        'debug' => array( 'FriendlyName' => 'Debugging', 'Type' => 'yesno',
            'Description' => 'Check this to turn debugging on', ),
    );

    return( $configArray );
}
// }}}
// {{{ payfast_link()
/**
 * payfast_link()
 *
 * Function used to generate code for form to redirect to PayFast
 *
 * @param mixed $params
 * @return
 */
function payfast_link( $params )
{
    // Include the PayFast common file
    define( 'PF_DEBUG', ( $params['debug'] == 'on' ? true : false ) );
    require_once('payfast/payfast_common.inc');

    require_once( 'payfast/' . getVer() );
    $subscriptionEnabled = $params['enable_recurring'] == 'on' ? true : false;
    $forceSubscription = $params['force_recurring'] == 'on' ? true : false;
    $enableSingleToken = $params['enable_single_token'] == 'on' ? true : false;
    $output = '';
    $subscriptionData = array();
    $forceOneTime = true;

    $invoiceItems = getInvoiceItems( $params['invoiceid'] );

    $invoiceHostingItems = getInvoiceHostingItems($params['invoiceid']);
    $item = $invoiceHostingItems[0];
    $hosting = getHosting( $item['relid'] );

    $tblhosting = Illuminate\Database\Capsule\Manager::table('tblhosting')
        ->where('id', $item['relid'])
        ->get();

    $subscriptionId = $tblhosting[0]->subscriptionid;

    if( $subscriptionEnabled )
    {
        $forceOneTime = false;
        $invoiceHostingItems = getInvoiceHostingItems($params['invoiceid']);

        // Determine that there is only one hosting subscription
        // Select the items from the invoice
        if( count( $invoiceHostingItems ) > 0 )
        {
            $invoiceHostingItems = getInvoiceHostingItems($params['invoiceid']);
            $item = $invoiceHostingItems[0];

            $hosting = getHosting( $item['relid'] );

            if ( $enableSingleToken )
            {
                $clientSubId = getSubscriptionId($hosting['userid']);
            }

            if( !$forceOneTime )
            {
                $subscriptionData['custom_str2'] = $hosting['orderid'];
                $subscriptionData['subscription_type'] = 2;
            }
        }
//        else
//        {
//            // If multiple hosting subscription, only show the invoice total
//            $forceOneTime = true;
//        }
    }

    $pfHost = ( ( $params['test_mode'] == 'on' ) ? 'sandbox' : 'www' ) . '.payfast.co.za';
    $payfastUrl = 'https://'. $pfHost .'/eng/process';

    // If NOT test mode, use normal credentials
    if( $params['test_mode'] != 'on' )
    {
        $merchantId = $params['merchant_id'];
        $merchantKey = $params['merchant_key'];
    }
    // If test mode, use generic sandbox credentials
    else
    {
        $merchantId = '10004002';
        $merchantKey = 'q1cd2rdny4a53';
    }

    // Create URLs
    if ( substr( $params['systemurl'], -1 ) == '/' )
    {
        $params['systemurl'] = substr_replace( $params['systemurl'], '', -1 );
    }
    $returnUrl = $params['systemurl'] .'/viewinvoice.php?id='. $params['invoiceid'];
    $cancelUrl = $params['systemurl'] .'/viewinvoice.php?id='. $params['invoiceid'];
    $notifyUrl = $params['systemurl'] .'/modules/gateways/callback/payfast.php';

    // Construct data for the form
    $data = array(
        // Merchant details
        'merchant_id' => $merchantId,
        'merchant_key' => $merchantKey,
        'return_url' => $returnUrl,
        'cancel_url' => $cancelUrl,
        'notify_url' => $notifyUrl,

        // Buyer Details
        'name_first' => urlencode( $params['clientdetails']['firstname'] ),
        'name_last' => urlencode( $params['clientdetails']['lastname'] ),
        'email_address' => trim($params['clientdetails']['email']),

        // Item details
        'm_payment_id' => $params['invoiceid'],
        'amount' => number_format( $params['amount'], 2, '.', '' ),
        'item_name' => $params['companyname'] .' purchase, Invoice ID #'. $params['invoiceid'],
        'item_description' => $params['companyname'] .' purchase, Order ID #'. $hosting['orderid'],
        'custom_str1' => $params['basecurrency']
    );

    if( !$forceOneTime )
    {
        if ( $forceSubscription || $subscriptionEnabled )
        {
            $dataForSig = array_merge( $data, $subscriptionData );
            $subscriptionData['signature'] = generateSignature( $params, $dataForSig );
        }
    }

    $data['signature'] = generateSignature($params, $data);

    $data['user_agent'] = 'WHMCS 6.x';
    if( !$forceOneTime && ( $subscriptionEnabled || $forceSubscription ) && !isset( $clientSubId['subscriptionid'] ) )
    {
        $button = '<input type="image" align="centre" src="'. $params['systemurl']. '/modules/gateways/payfast/images/light-small-subscribe.png" value="Subscribe Now">';
        $output .= generateForm( $payfastUrl, array_merge( $data, $subscriptionData ), $button, $subscriptionId );
        $output .= '&nbsp;';
    }

    if( $forceOneTime || ( !$forceOneTime && !$forceSubscription ) && ( !isset( $clientSubId['subscriptionid'] ) || !$enableSingleToken ) )
    {
        $output .= generateForm( $payfastUrl, $data, null, $subscriptionId, $params['systemurl'], $clientSubId['subscriptionid'] );
    }

    if ( $enableSingleToken && !empty( $clientSubId['subscriptionid'] ) )
    {
        $output .= generateForm( $payfastUrl, $data, null, $subscriptionId, $params['systemurl'], $clientSubId['subscriptionid'], $hosting['userid'] );
    }

    return( $output );
}

/**
 * generateSignature
 *
 *
 *
 * @date ${date}
 * @version 1.0.0
 * @access
 *
 * @author Ron Darby <ron.darby@payfast.co.za>
 * @since 1.0.0
 *
 * * @param $params
 * @param $dataForSig
 * @param $secureString
 * @param $data
 * @return mixed
 *
 *
 */
function generateSignature( $params, $dataForSig )
{
    $secureString = '';
    foreach ( $dataForSig as $k => $v )
    {
        $secureString .= $k . '=' . urlencode( htmlspecialchars( trim( $v ) ) ) . '&';
    }

    if ( empty( $params['passphrase'] ) && $params['test_mode'] != 'on' )
    {
        $secureString = substr($secureString, 0, -1);
    }
    elseif ( $params['test_mode'] == 'on' )
    {
        $secureString .= 'passphrase=payfast';
    }
    else
    {
        $secureString .= 'passphrase=' . $params['passphrase'];
    }

    return  md5($secureString);
}

/**
 * generateForm
 *
 *
 *
 * @date ${date}
 * @version 1.0.0
 * @access
 *
 * @author Ron Darby <ron.darby@payfast.co.za>
 * @since 1.0.0
 *
 * * @param $payfastUrl
 * @param $data
 * @return button html
 *
 *
 */
function generateForm( $payfastUrl, $data, $button = null, $subscriptionId = null, $systemUrl, $clientSubId, $userId = null )
{
    if ( empty ( $subscriptionId ) && is_null( $clientSubId ) )
    {
        $output = '<form id="payfast_form" name="payfast_form" action="' . $payfastUrl . '" method="post">';
        foreach ( $data as $name => $value )
        {
            $output .= '<input type="hidden" name="' . $name . '" value="' . $value . '">';
        }
        $output .= '<input type="hidden" name="subscriptionid" value="' . $clientSubId . '">';

        if ( is_null( $button ) )
        {
            $output .= '<input type="image" align="centre" src="' . $systemUrl . '/modules/gateways/payfast/images/light-small-paynow.png" value="Pay Now">';
        }
        else
        {
            $output .= $button;
        }
        $output .= '</form>';
        return $output;
    }

    if ( !empty( $clientSubId ) )
    {
        if ( $userId != null )
        {
            LogActivity( 'PayFast single subscription token ' . $clientSubId . ' set to user ' . $userId );
        }
        
        $output = '<form id="payfast_form" name="payfast_form" action="'.$systemUrl.'/modules/gateways/payfast/adhoc.php" method="post" >';
        foreach ( $data as $name => $value )
        {
            $output .= '<input type="hidden" name="' . $name . '" value="' . $value . '">';
        }
        $output .= '<input type="hidden" name="subscriptionid" value="' . $clientSubId . '">';

        if ( is_null( $button ) )
        {
            //   $output .= '<input type="image" align="centre" src="' . $systemUrl . '/modules/gateways/payfast/images/light-small-paynow.png" value="Pay Now" id="paynow">';
        }
        else
        {
            $output .= $button;
        }

        $output .= '</form>';

        $output .= 'Processing Payment';
        return $output;
    }
}

function getVer()
{
    //return 'v5_include.php';
    $v6 = class_exists('Illuminate\Database\Capsule\Manager');
    if( $v6 )
    {
        return 'v6_include.php';
    }
    else
    {
        return 'v5_include.php';
    }
}

// }}}
?>