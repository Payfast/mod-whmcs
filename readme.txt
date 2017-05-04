PayFast WHMCS v4 Module v1.13 for WHMCS v4.5.2 & v5.0, v1.21 for WHMCS v6.0 v1.4.* for WHMCS v6.* and v7.*
----------------------------------------------------------------------------------------------------------
Copyright (c) 2008 PayFast (Pty) Ltd
You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.

INTEGRATION:
1. Unzip the module to a temporary location on your computer
2. Copy the “modules” and "includes" folders from the archive to your base “whmcs” folder (using FTP program or similar)
- This should NOT overwrite any existing files or folders and merely supplement them with the PayFast files
- This is however, dependent on the FTP program you use
3. Login to the WHMCS Administrator console
4. Using the main menu, navigate to Setup ? Payment Gateways
5. Select “PayFast” from the “Activate Gateway” drop-down list and click “Activate”
6. Enter the following details under the “PayFast” heading:
7. Merchant ID = <Integration page>
8. Merchant Key = <Integration page>
9. Check Test Mode
10. Click “Save Changes”
11. The module is now and ready to be tested with the Sandbox.

How do I setup and manage recurring billing (WHMCS v6.* and v7.*)?
If you already have subscriptions setup on your WHMCS site with PayFast module v1.3.1 or less, those subscriptions will continue to be charged as before until cancelled.
The 'Pay Now' button will not, however, be available on the invoices of subscriptions that are already setup due to the new subscription method. In order to migrate your clients to the new method the old subscription will need to be cancelled on PayFast, as well as WHMCS, and then the client will need to sign up again via your WHMCS site.

1. Log in to your PayFast account and navigate to settings->integration
2. Click on 'Enable' or 'Edit' next to Recurring Billing and enable Ad Hoc payments (once setup, WHMCS subscriptions will be found under 'Ad Hoc Agreements' on your PayFast account)
3. Log into the admin dashboard of your WHMCS site and navigate to the PayFast configuration page as before
4. Select 'Enable' or 'Force' recurring billing
5. Once the subscription has been setup it can be managed on the client profile page, under the products/services tab on your WHMCS site admin dashboard
6. Note, it is essential that you have your cron setup in order to charge the subscription.

I’m ready to go live! What do I do?
In order to make the module “LIVE”, follow the instructions below:

1. Login to the WHMCS Administrator console
2. Using the main menu, navigate to Setup -> Payment Gateways
3. Under the “PayFast” heading, uncheck the “Test Mode” item
4. Click “Save Changes”

******************************************************************************
*                                                                            *
*    Please see the URL below for all information concerning this module:    *
*                                                                            *
*                     https://www.payfast.co.za/shopping-carts/whmcs/        *
*                                                                            *
******************************************************************************
