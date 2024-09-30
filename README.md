# mod-whmcs

## Payfast WHMCS Module for WHMCS v8.11

This is the Payfast module for WHMCS. Please feel free to [contact the Payfast support team](https://payfast.io/contact/) should you require any assistance.

## Installation
1. Unzip the module to a temporary location on your computer.
2. Copy the “modules” folder from the archive to your base “whmcs” folder (using FTP program or similar)
- This should NOT overwrite any existing files or folders and merely supplement them with the Payfast files.
- This is however, dependent on the FTP program you use.
3. Login to the WHMCS Administrator console.
4. Using the main menu, navigate to Setup -> Apps & Integrations and search for "Payfast".
5. Click “Payfast” from the search results list, and then click “Activate”
6. Enter the following details under the “Payfast” heading:
7. Merchant ID = <Integration page>
8. Merchant Key = <Integration page>
8. PassPhrase = <Integration page>
9. Check Sandbox Test Mode (if applicable).
10. Click “Save Changes”.
11. The module is now and ready to be tested with the Sandbox.

How do I setup and manage recurring billing?
If you already have subscriptions setup on your WHMCS site with Payfast module v6 or less, those subscriptions will continue to be charged as before until cancelled.

On Your Payfast Account
1. Log in to your Payfast account and navigate to settings->integration.
2. Click on 'Enable' or 'Edit' next to Recurring Billing and enable Ad Hoc payments (once setup, WHMCS subscriptions will be found under 'Ad Hoc Agreements' on your Payfast account).
   On your admin dashboard of your WHMCS site
3. Log into the admin dashboard of your WHMCS site and navigate to the Payfast configuration page as before
4. Select 'Enable recurring billing'. This will enable the option for clients to subscribe.
5. Optionally select 'Force recurring billing' which will force clients to subscribe to adhoc subscriptions.
6. (Optional if 'Force recurring billing' is selected)Turn off Auto Redirect on Checkout under Setup -> General Settings -> Ordering "Auto Redirect on Checkout" to automatically take the user to the invoice. This setting is required to allow clients the option to pay subscription invoices once off or to subscribe to  tokenized billing(adhoc).
7. Once the subscription has been setup it can be managed on the client profile page, under the products/services tab on your WHMCS site admin dashboard.

I’m ready to go live! What do I do?
In order to make the module “LIVE”, follow the instructions below:

1. Login to the WHMCS Administrator console
2. Using the main menu, navigate to Setup -> Payments -> Payment Gateways
3. Under the “Payfast” heading, uncheck the “Test Mode” item
4. Click “Save Changes”

Please [click here](https://payfast.io/integration/shopping-carts/whmcs/) more information concerning this module.

## Collaboration

Please submit pull requests with any tweaks, features or fixes you would like to share.
