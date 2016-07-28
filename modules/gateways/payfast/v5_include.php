<?php
/**
 * Created by PhpStorm.
 * User: Ron
 * Date: 2016/06/28
 * Time: 12:02 PM
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