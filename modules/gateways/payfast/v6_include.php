<?php

/**
 * getInvoiceItems
 *
 *
 *
 * @date 2016-06-29
 * @version 1.0.0
 * @access
 *
 * @author Ron Darby <ron.darby@payfast.co.za>
 * @since 1.0.0
 *
 * @param $invoiceId
 * @return mixed
 *
 *
 */
function getInvoiceHostingItems($invoiceId)
{
    $invoiceHostingItems = Illuminate\Database\Capsule\Manager::table('tblinvoiceitems')
        ->where('invoiceid', $invoiceId)
        ->where('type', 'Hosting')
        ->get();
    return json_decode(json_encode($invoiceHostingItems), true);
}

/**
 * getInvoiceItems
 *
 *
 *
 * @date 2016-06-29
 * @version 1.0.0
 * @access
 *
 * @author Ron Darby <ron.darby@payfast.co.za>
 * @since 1.0.0
 *
 * * @param $invoiceId
 * @return mixed
 *
 *
 */
function getInvoiceItems($invoiceId )
{
    $invoiceHostingItems = Illuminate\Database\Capsule\Manager::table('tblinvoiceitems')
        ->where('invoiceid', $invoiceId)
        ->get();
    return json_decode(json_encode($invoiceHostingItems), true);
}

/**
 * getInvoiceStatus
 *
 *
 *
 * @date 2016-06-29
 * @version 1.0.0
 * @access
 *
 * @author Brendon Posen <brendon.posen@payfast.co.za>
 * @since 1.0.0
 *
 * * @param $invoiceId
 * @return mixed
 *
 *
 */
function getInvoiceStatus( $invoiceId )
{
    $invoice = Illuminate\Database\Capsule\Manager::table('tblinvoices')
        ->where('id', $invoiceId)
        ->get();
    return json_decode(json_encode($invoice), true);
}

/**
 * getHosting
 *
 *
 *
 * @date 2016-06-29
 * @version 1.0.0
 * @access
 *
 * @author Ron Darby <ron.darby@payfast.co.za>
 * @since 1.0.0
 *
 * * @param $id
 * @return array
 *
 *
 */
function getHosting($id )
{
    $hosting = Illuminate\Database\Capsule\Manager::table('tblhosting')
        ->where('id', $id)
        ->first();
    return (array)$hosting;
}

/**
 * getProduct
 *
 *
 *
 * @date 2016-06-29
 * @version 1.0.0
 * @access
 *
 * @author Ron Darby <ron.darby@payfast.co.za>
 * @since 1.0.0
 *
 * * @param $id
 * @return array
 *
 *
 */
function getProduct($id )
{
    $product = Illuminate\Database\Capsule\Manager::table('tblproducts')
        ->where('id', $id )
        ->first();
    return (array)$product;
}

/**
 * setAdHocPayment
 *
 *
 *
 * @date 2016-11-18
 * @version 1.0.0
 * @access
 *
 * @author Brendon Posen<brendon.posen@payfast.co.za>
 * @since 1.0.0
 *
 * @param $orderId
 * @return boolean
 *
 */
function setAdHocPaymentMethod( $orderId )
{
    Illuminate\Database\Capsule\Manager::table('tblhosting')
        ->where('orderid', $orderId)
        ->update(
            [
                'paymentmethod' => 'payfast-adhoc',
            ]
        );

    return true;
}

/**
 * setDomainStatus
 *
 *
 *
 * @date 2016-11-18
 * @version 1.0.0
 * @access
 *
 * @author Brendon Posen<brendon.posen@payfast.co.za>
 * @since 1.0.0
 *
 * @param $orderId
 * @return boolean
 *
 *
 */
function setDomainStatus( $orderId )
{
    Illuminate\Database\Capsule\Manager::table('tblhosting')
        ->where('orderid', $orderId)
        ->update(
            [
                'domainstatus' => 'Active',
            ]
        );

    return true;
}

/**
 * setSubscriptionId
 *
 *
 *
 * @date 2016-11-18
 * @version 1.0.0
 * @access
 *
 * @author Brendon Posen<brendon.posen@payfast.co.za>
 * @since 1.0.0
 *
 *  @param $subId
 * @param $orderId
 * @return boolean
 *
 *
 */
function setSubscriptionId( $subId, $orderId )
{
    Illuminate\Database\Capsule\Manager::table('tblhosting')
        ->where('orderid', $orderId)
        ->update(
            [
                'subscriptionid' => $subId,
            ]
        );

    return true;
}

/**
 * setTblHostingCancelStatus
 *
 *
 *
 * @date 2016-11-18
 * @version 1.0.0
 * @access
 *
 * @author Brendon Posen<brendon.posen@payfast.co.za>
 * @since 1.0.0
 *
 * @param $orderId
 * @return boolean
 *
 *
 */
function setTblHostingCancelStatus( $orderId )
{
    Illuminate\Database\Capsule\Manager::table('tblhosting')
        ->where('orderid', $orderId)
        ->update(
            [
                'domainstatus' => 'Cancelled',
            ]
        );

    return true;
}

/**
 * setTblOrdersCancelStatus
 *
 *
 *
 * @date 2016-11-18
 * @version 1.0.0
 * @access
 *
 * @author Brendon Posen<brendon.posen@payfast.co.za>
 * @since 1.0.0
 *
 * @param $invoiceId
 * @return boolean
 *
 *
 */
function setTblOrdersCancelStatus( $invoiceId )
{
    Illuminate\Database\Capsule\Manager::table('tblorders')
        ->where('invoiceid', $invoiceId)
        ->update(
            [
                'status' => 'Cancelled',
            ]
        );

    return true;
}