PayFast WHMCS Module for WHMCS v7.10.2 
----------------------------------------------------------------------------------------------------------
Copyright (c) 2008 PayFast (Pty) Ltd
You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.

INTEGRATION:
1. Unzip the module to a temporary location on your computer.
2. Copy the “modules” folder from the archive to your base “whmcs” folder (using FTP program or similar)
- This should NOT overwrite any existing files or folders and merely supplement them with the PayFast files.
- This is however, dependent on the FTP program you use.
3. Login to the WHMCS Administrator console.
4. Using the main menu, navigate to Setup -> Payments -> Payment Gateways.
5. Select “PayFast” from the “Activate Gateway” drop-down list and click “Activate”
6. Enter the following details under the “PayFast” heading:
7. Merchant ID = <Integration page>
8. Merchant Key = <Integration page>
9. Check Test Mode.
10. Click “Save Changes”.
11. The module is now and ready to be tested with the Sandbox.

How do I setup and manage recurring billing?
If you already have subscriptions setup on your WHMCS site with PayFast module v6 or less, those subscriptions will continue to be charged as before until cancelled.

On Your PayFast Account
1. Log in to your PayFast account and navigate to settings->integration.
2. Click on 'Enable' or 'Edit' next to Recurring Billing and enable Ad Hoc payments (once setup, WHMCS subscriptions will be found under 'Ad Hoc Agreements' on your PayFast account).
On your admin dashboard of your WHMCS site 
3. Log into the admin dashboard of your WHMCS site and navigate to the PayFast configuration page as before
4. Select 'Enable recurring billing'. This will enable the option for clients to subscribe. 
5. Optionally select 'Force recurring billing' which will force clients to subscribe to adhoc subscriptions.
6. (Optional if 'Force recurring billing' is selected)Turn off Auto Redirect on Checkout under Setup -> General Settings -> Ordering "Auto Redirect on Checkout" to automatically take the user to the invoice. This setting is required to allow clients the option to pay subscription invoices once off or to subscribe to  tokenized billing(adhoc).
7. Once the subscription has been setup it can be managed on the client profile page, under the products/services tab on your WHMCS site admin dashboard.

I’m ready to go live! What do I do?
In order to make the module “LIVE”, follow the instructions below:

1. Login to the WHMCS Administrator console
2. Using the main menu, navigate to Setup -> Payments -> Payment Gateways
3. Under the “PayFast” heading, uncheck the “Test Mode” item
4. Click “Save Changes”

******************************************************************************
*                                                                            *
*    Please see the URL below for all information concerning this module:    *
*                                                                            *
*                     https://www.payfast.co.za/shopping-carts/whmcs/        *
*                                                                            *
******************************************************************************
