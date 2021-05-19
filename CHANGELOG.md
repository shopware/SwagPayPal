# REPLACE_GLOBALLY_WITH_NEXT_VERSION
- PPI-246 - Added option for advanced logging

# 3.0.3
- PPI-20 - Fix Webhooks when payment status is already set identically
- PPI-235 - Fix Webhooks not correctly registered for separate Sales Channel credentials
- PPI-238 - Fix issue with order not being linked in disputes of Plus payments
- PPI-243 - Added more PayPal-specific transaction data to custom fields
- PPI-265 - Fix encoding on shortened Zettle product descriptions

# 3.0.2
- NEXT-15014 - Improve ACL handling

# 3.0.1
- PPI-65 - Improved compatibility for Shopware 6.4 with Zettle
- PPI-255 - Fix Express checkout if data protection checkbox is required
- PPI-263 - Plugin is valid for the `dal:validate` console command

# 3.0.0
- PPI-65 - Added compatibility for Shopware 6.4
- PPI-239 - Rebranded iZettle to Zettle

# 2.2.3
- PPI-256 - Fix canceling of finalized orders by using the browser history

# 2.2.2
- PPI-244 - Fix issue on API authentication
- PPI-221 - Fix issue with oversized product descriptions in Zettle

# 2.2.1
- PPI-241 - Improve cancelling order transactions with the ScheduledTask

# 2.2.0
- PPI-191 - Introduce PayPal disputes overview

# 2.1.2
- PPI-211 - Fix sending of shipping address name to PayPal
- PPI-222 - Add Express Checkout button to search page and wish list page
- PPI-229 - Order transactions with a stale PayPal payment will be cancelled with a ScheduledTask
- PPI-231 - Fix deletion of rule for payment upon invoice during uninstall
- PPI-234 - Improve entity definition

# 2.1.1
- PPI-208 - Fix redirect of cancelled Plus payment on Shopware 6.3.3.x
- PPI-210 - Improve handling of promotions during Express Checkout
- PPI-220 - Fix saving of customer telephone number on Express Checkout
- PPI-223 - Solves an issue with Express Checkout button state
- PPI-224 - Fix Express Checkout for Shopware versions prior 6.3.2.0

# 2.1.0
- PPI-174 - Cart and order line items are now sent with their SKU
- PPI-174 - Added events to adjust line items which are sent to PayPal
- PPI-202 - Fix PayPal checkout for customers with net prices

# 2.0.2
- PPI-199 - Improve webhook log messages
- PPI-200 - Fix submitting of carts with discounts

# 2.0.1
- PPI-171 - Message queue is now only used if there are iZettle Sales Channels
- PPI-172 - Improve capturing and refunding process
- PPI-177 - Fix PayPal Express Checkout buttons in product listings
- PPI-185 - Improve error handling of the PayPal tab in the order module
- PPI-194 - Fix deregister of webhooks on Sales Channel deletion
- PPI-196 - Improve PayPal Plus checkout process
- PPI-197 - Fix "Submit cart" functionality

# 2.0.0
- PPI-182 - Improve webhook registration
- PT-11875 - Migration to PayPal API v2 for the following features: PayPal, Express Checkout and Smart Payment Buttons

# 1.10.0
- PPI-159 - Added ACL privileges to the PayPal modules
- PPI-161 - Fix credentials form in first run wizard

# 1.9.3
- PPI-67 - Reimplemented activation of webhooks
- PPI-110 - Added restrictions set by PayPal for Alternative Payment Methods
- PPI-114 - Minor onboarding process improvements
- PPI-145 - Minor adjustments to settings page
- PPI-151 - Fixes error with payments with already existing order numbers
- PPI-158 - Fixes error during update to versions 1.7.0 or higher, if no configuration is available

# 1.9.2
- PPI-149 - Fixes error during communication with iZettle

# 1.9.1
- PPI-141 - Improve performance of API to PayPal

# 1.9.0
- PPI-1 - Fixes the mobile layout of checkout finish page for "Pay Upon Invoice"
- PPI-68, PPI-118, PPI-136 - Improved API struct usage for third party extensions
- PPI-69 - The Express button is now hidden, when the PayPal payment method is disabled
- PPI-97 - Fixes error during Express Checkout, if required fields are not sent by PayPal
- PPI-124 - Fixes error display during communication with PayPal
- PPI-128 - Fixes issue during Express Checkout, if changes are made on confirm page
- PPI-130 - Adds new event, which is emitted when the Plus iFrame is loaded
- PT-11048 - Introduce iZettle integration (Point of Sales)

# 1.8.4
- PPI-125 - Shopware 6.3.2.0 compatibility

# 1.8.3
- PPI-70 - Order number is now correctly submitted to PayPal for payments with Express Checkout, PLUS and Smart Payment Buttons

# 1.8.2
- PPI-46 - Fixes issue on refund without amount
- PPI-47, PPI-48 - Enhancement of the PayPal API elements

# 1.8.1
- PPI-32, PPI-35 - Improve extensibility for third party plugins
- PPI-36 - Add new PayPal API elements

# 1.8.0
- PT-11912 - Storefront snippets now get auto registered
- PT-11920 - Shopware 6.3 compatibility

# 1.7.3
- PT-11946 - Fix update with deactivated plugin
- PT-11949 - Fix setting paypal as the default payment method in the settings menu for all Sales Channels

# 1.7.2
- PT-10491 - Removed internally used custom field entity for transaction IDs
- PT-11627 - Order transactions now have the state "In Progress" when the payment process has been started
- PT-11680 - Removed unknown Sales Channel types from selection in settings
- PT-11681 - Fix order details header in Administration, if accessing payment details directly
- PT-11860 - Fix order confirmation email language with PayPal Plus
- PT-11888 - Minor performance improvement when creating a payment
- PT-11903 - Fix failed transaction status for user-canceled PayPal Plus payments
- PT-11928 - Limited length of text input fields in administration according to PayPal API

# 1.7.1
- PT-11884 - If PayPal is not available, Plus and Smart Payment Buttons are no longer loaded

# 1.7.0
- PT-11669 - Add compatibility with the after order payment process
- PT-11707 - Custom form parameter of the order confirm page are no longer ignored
- PT-11748 - Fix redirect URL for PayPal Plus and Express Checkout. Changed webhook URL to be independent of a storefront
- PT-11773 - Fix buying of Custom Products with PayPal
- PT-11813 - Error handling for Express Checkout button
- PT-11858 - Improved handling of multiple transactions per order
- PT-11869 - Improved handling of payments which were cancelled by customers

# 1.6.0
- PT-11519 - Registers webhooks with HTTPS
- PT-11593 - Adds hint for "Payment acquisition" option to clarify usage with PayPal PLUS
- PT-11704 - Fix displaying of Express Checkout Button on paginated product listing pages
- PT-11706 - Country states are now saved on Express Checkout
- PT-11717 - Adds PayPal cookies to the cookie manager

# 1.5.2
- PT-10502 - Canceling the payment on the PayPal site no longer leads to errors
- PT-11710 - Fix installation of plugin in environments where the default language is not de-DE or en-GB

# 1.5.1
- PT-10640 - Fix SalesChannel api routes
- PT-10897 - Locale code validation for Smart Payment Buttons and Express Checkout
- PT-11294 - Error handling for Smart Payment Buttons
- PT-11582 - Fix webhook registration
- PT-11637 - Improve capture and refund workflow

# 1.5.0
- NEXT-8322 - Shopware 6.2 compatibility
- PT-10654 - Activate and set PayPal as default for the selected Saleschannel in the settings module
- PT-11599 - Fixes a bug where PayPal Plus could not be configured individually per Saleschannel

# 1.4.0
- PT-11540 - Corrects remaining amount for multiple partial refunds
- PT-11541 - Improved behaviour of multiple partial refunds & captures
- PT-11606 - Shopware 6.2 compatibility

# 1.3.0
- PT-10448 - Adds onboarding to get API credentials with PayPal login in settings module
- PT-11292 - Adds possibility to enter separate credentials for sandbox mode in first run wizard
- PT-11498 - Adds PayPal Express Button to QuickView from CMS Extensions plugin
- PT-11550 - Fix usage of sandbox credentials after an update

# 1.2.0
- PT-11233 - Do not show PayPal Express Button on the product detail page, if the product is in clearance
- PT-11292 - Add possibility to enter separate credentials for sandbox mode

# 1.1.1
- PT-11443 - Solves an issue with the error handling with the paypal credentials
- PT-11475 - Improved processing of vouchers during checkout

# 1.1.0
- PT-11276 - Add banner for advertising installments

# 1.0.0
- PT-11181, PT-11275 - Add PayPal PLUS integration
- PT-11277 - The cart and order number submitting is now active by default

# 0.13.0
- Shopware 6.1 compatibility

# 0.12.0
- PT-10287 - Adds possibility to add the invoice number, description or reason while refunding an order
- PT-10705 - The PayPal settings are now in an own administration module
- PT-10771 - Improves displaying of Smart Payment Buttons
- PT-10775 - Improves order transaction state handling
- PT-10809 - Smart Payment Buttons can now be styled separately from Express Checkout Button
- PT-10821 - Fixes error on sale complete webhook execution
- NEXT-4282 - Reinstall of plugin does not duplicate configuration entries anymore

# 0.11.2
- PT-10733 - Fixes problem when automatically fetching API credentials in first run wizard

# 0.11.1
- PT-10755 - Fixes error while uninstalling and configuration error

# 0.11.0
- PT-10391 - Implements pay upon invoice
- PT-10695 - Adds error logging for API calls
- PT-10702 - Changes URL for Smart Payment Buttons javascript
- PT-10715 - Paypal is selected correctly again as payment method for Express Checkout
- PT-10723 - Smart Payment Buttons now no longer complete the order directly
- PT-10729 - The PayPal payment description now shows available payments with icons

# 0.10.1
- Improves link generation for Javascript API calls

# 0.10.0
- Adds onboarding for the first run wizard

# 0.9.0
- First version of the PayPal integrations for Shopware 6
