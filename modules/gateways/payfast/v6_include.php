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
 * * @param $params
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