<?php
/**
 * Copyright (c) 2008 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 */

//return;
/**
 * getInvoiceHostingItems
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
 * @return array
 *
 *
 */
function getInvoiceHostingItems($invoiceId )
{
    $resource = full_query("SELECT * FROM `tblinvoiceitems` WHERE `type`='Hosting' AND  `invoiceid`='" . $invoiceId. "'");

    $items = [];

    while( $item = mysql_fetch_assoc( $resource ) )
    {
        $items[] = $item;
    }
    return $items;
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
 * @return array
 *
 *
 */
function getInvoiceItems($invoiceId )
{
    $resource = full_query("SELECT * FROM `tblinvoiceitems` WHERE `invoiceid`='" . $invoiceId. "'");

    $items = [];

    while( $item = mysql_fetch_assoc( $resource ) )
    {
        $items[] = $item;
    }
    return $items;
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
 * * @param $relid
 * @return array
 *
 *
 */
function getHosting($relid )
{
    $resource = full_query("SELECT * FROM `tblhosting` WHERE `id`='" . $relid. "'");
    $item = mysql_fetch_assoc( $resource );
    return $item;
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
    $resource = full_query("SELECT * FROM `tblproducts` WHERE `id`='" . $id. "'");
    $item = mysql_fetch_assoc( $resource );
    return $item;
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
    full_query("UPDATE `tblhosting` SET `subscriptionid` = '" . $subId . "' WHERE `orderid` = '" . $orderId . "'");

    return true;
}