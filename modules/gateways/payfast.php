<?php
/**
 * payfast.php
 * 
 * Copyright (c) 2010-2012 PayFast (Pty) Ltd
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
 * @copyright  2010-2012 PayFast (Pty) Ltd
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
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
            'Description' => '!!!!!!!!DO NOT SET THIS UNLESS YOU HAVE SET IT ON THE <a href="http://www.payfast.co.za/acc/integration">Integration</a> PAGE ON PayFast!!!!!!!!', ),
        'enable_recurring' => array( 'FriendlyName' => 'Enable Recurring Billing', 'Type' => 'yesno', 'Description' => '!!!!!! You need to enable Subscriptions on the <a href="http://www.payfast.co.za/acc/integration">Integration</a> page on PayFast!!!!!!!!'),
        'force_recurring' => array( 'FriendlyName' => 'Force Recurring Billing', 'Type' => 'yesno', 'Description' => 'Hide the one time payment when a subscription can be created'),
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
    $output = '';
    $subscriptionData = array();
    $forceOneTime = true;
    
    $invoiceItems = getInvoiceItems( $params['invoiceid'] );

    if( $subscriptionEnabled )
    {
        $invoiceHostingItems = getInvoiceHostingItems($params['invoiceid']);

        // Determine that there is only one hosting subscription
        // Select the items from the invoice
        if( count( $invoiceHostingItems ) == 1 )
        {
            $forceOneTime = false;
            $item = $invoiceHostingItems[0];

            $hosting = getHosting( $item['relid'] );
            
            $product = getProduct( $hosting['packageid'] );

            $frequencyMap = array(
                'Monthly' => 3,
                'Quarterly' => 4,
                'Semi-Annually' => 5,
                'Annually' => 6
            );
            switch ( $hosting['billingcycle'] )
            {
                case 'Monthly':
                case 'Quarterly':
                case 'Semi-Annually':
                case 'Annually':
                    $frequency = $frequencyMap[$hosting['billingcycle']];
                    break;
                case 'One Time':
                default;
                    $forceOneTime = true;
                    break;
            }

            // If only one hosting subscription, get the frequency and cycles and build up the subscription button
            if(!$forceOneTime)
            {
                $subscriptionData['item_name'] = $item['description'];
                $subscriptionData['subscription_type'] = 1;
                $subscriptionData['recurring_amount'] = $hosting['amount'];
                $subscriptionData['frequency'] = $frequency;
                if($product['recurringcycles'] > 0)
                {
                    $subscriptionData['cycles'] = $product['recurringcycles'];
                }
            }
        }
        else
        {
            // If multiple hosting subscription, only show the invoice total
            $forceOneTime = true;
        }


    


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
        $merchantId = '10000100';
        $merchantKey = '46f0cd694581a';
    }

    // Create URLs
    if ( substr( $params['systemurl'], -1 ) == '/' )
    {
        $params['systemurl'] = substr_replace( $params['systemurl'], '', -1 );
    }
    $returnUrl = $params['systemurl'] .'/viewinvoice.php?id='. $params['invoiceid'];
    $cancelUrl = $params['systemurl'] .'/viewinvoice.php?id='. $params['invoiceid'];
    $notifyUrl = $params['systemurl'] .'/modules/gateways/callback/payfast.php';
    
    // Create description
    // Line item details are not available in the $params variable
    $description = '';
    
    foreach( $invoiceItems as $k=>$item )
    {
        $description .= $item['description'] . "|";
    }

    // Construct data for the form
    $data = array(
        // Merchant details
        'merchant_id' => $merchantId,
        'merchant_key' => $merchantKey,
        'return_url' => $returnUrl,
        'cancel_url' => $cancelUrl,
        'notify_url' => $notifyUrl,

        // Buyer Details
        'name_first' => $params['clientdetails']['firstname'],
        'name_last' => $params['clientdetails']['lastname'],
        'email_address' => trim($params['clientdetails']['email']),

        // Item details
        'm_payment_id' => $params['invoiceid'],
        'amount' => number_format( $params['amount'], 2, '.', '' ),
    	'item_name' => $params['companyname'] .' purchase, Invoice ID #'. $params['invoiceid'],
    	//'item_description' => $description
        );

    if( !$forceOneTime )
    {
        $dataForSig = array_merge( $data, $subscriptionData );
        $subscriptionData['signature'] = generateSignature($params, $dataForSig);

        //logActivity( print_r( $dataForSig,true) );
    }

    $data['signature'] = generateSignature($params, $data);


    $data['user_agent'] = 'WHMCS 6.x';
    if( !$forceOneTime )
    {
        $button = '<input type="submit" value="Subscribe Now">';
        $output .= generateForm( $payfastUrl, array_merge( $data, $subscriptionData ), $button );
        $output .= '&nbsp;';
    }

    if( $forceOneTime || (!$forceOneTime && !$forceSubscription ) )
    {
        $output .= generateForm( $payfastUrl, $data);
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
    foreach ($dataForSig as $k => $v) {
        $secureString .= $k . '=' . urlencode(htmlspecialchars(trim($v))) . '&';
    }

    if (empty($params['passphrase']) || $params['test_mode'] == 'on') {
        $secureString = substr($secureString, 0, -1);
    } else {
        $secureString .= 'passphrase=' . $params['passphrase'];
    }

    //logActivity( print_r( $params, true ) );

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
function generateForm($payfastUrl,  $data, $button = null)
{
    $output = '<form id="payfast_form" name="payfast_form" action="' . $payfastUrl . '" method="post">';
    foreach ($data as $name => $value) {
        $output .= '<input type="hidden" name="' . $name . '" value="' . $value . '">';
    }

    if( is_null( $button ) )
    {
        $output .= '<input type="submit" value="Pay Now" />';
    }
    else
    {
        $output .= $button;
    }
    $output .= '</form>';
    return $output;
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