PayFast WHMCS v4 Module v1.13 for WHMCS v4.5.2 & v5.0, v1.21 for WHMCS v6.0 v1.4.0 for WHMCS v6.* and v7.*
----------------------------------------------------------------------------------------------------------
Copyright (c) 2010-2017 PayFast (Pty) Ltd

LICENSE:
 
This payment module is free software; you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published
by the Free Software Foundation; either version 3 of the License, or (at
your option) any later version.

This payment module is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public
License for more details.

Please see http://www.opensource.org/licenses/ for a copy of the GNU Lesser
General Public License.

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
If you already have subscriptions setup on your WHMCS site with PayFast module v1.3.1 or less, those subscriptions will continue to operate as before until cancelled
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
