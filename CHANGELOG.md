# 9.6.4
- PPI-930 - Fixes a issue, where with a selected sales channel the inherited configuration was not working correctly
- PPI-1013 - Improves the reliability of syncing products to Zettle
- PPI-1014 - Fixes an issue, where images with special characters could not be synced to Zettle
- PPI-1016 - Fixes an issue, where shipping tracking codes were not synced with to long product names
- PPI-1024 - Fixes an issue, where card payments could fail if 3DS is not active for a given card

# 9.6.3
- PPI-1015 - Fixes an issue, where PayPal settings could not be saved in the Administration

# 9.6.2
- PPI-971 - Fixes an issue, where umlauts in the brand name were causing Apple Pay payments to be cancelled
- PPI-999 - Fixes an issue, where admin component overrides would block settings from being saved
- PPI-1002 - Fixes an issue, where the Apple Pay domain hint was not shown
- PPI-1008 - Fixes an issue, where payment status polling kept retrying with non-existent transactions
- PPI-1009 - Fixes an issue, where PayPal Plus was not shown in the Checkout
- PPI-1010 - Fixes an issue, where the compiled CSS could be broken in combination with other extensions

# 9.6.1
- PPI-942 - Fixes an issue, where cookies from Google Pay were displayed in the cookie banner despite being deactivated
- PPI-957 - Added quick links to the extension context menu and PayPal settings
- PPI-991 - Fixes an issue, where Trustly was erroneously offered
- PPI-995 - Fixes an issue, where the Zettle set up wizard could get stuck
- PPI-996 - Fixes an issue, where PayPal buttons could disappear during checkout with certain themes

# 9.6.0
- PPI-922 - Improved visibility of Apple Pay on browsers and devices that are not supported
- PPI-978 - Enabled Trustly for more compatible countries and currencies
- PPI-979 - Fixes an issue, where PUI wouldn't work for customers without a birthdate in after order process
- PPI-981 - Fixes an issue, where the payment status was sometimes not correctly set to failed if communication with PayPal failed
- PPI-987 - Fixes an issue, where creating Zettle Sales Channels or changing Zettle credentials failed
- PPI-988 - Fixes an issue, where SEPA, cards and Venmo may have been missing in the Checkout

# 9.5.0
- PPI-958 - Fixes an issue, where the credentials validation erroneously succeeded with invalid Merchant Payer IDs
- PPI-960 - Express checkout buttons are now available with enabled Double-Opt-In, a warning will be displayed in the settings
- PPI-961 - Fixes an issue, where the setting of the 'Pay Later' banner on the login page was not saved
- PPI-965 - Fixes an issue, where an error in the checkout could lead to an infinite reload loop if SwagCommercial was installed

# 9.4.0
- PPI-807 - Improved script loading performance in the Storefront
- PPI-924 - Added more explicit error messages for errors happening during express checkout
- PPI-929 - Added onboarding notice for Apple and Google Pay payment methods
- PPI-950 - Changed the shipment carrier field to a dropdown in the Administration
- PPI-951 - Fixes an issue, where only generic error messages were displayed during checkout without Smart Payment Buttons
- PPI-953 - Fixes an issue, where shipping tracking codes were not synced with an invalid carrier
- PPI-956 - Improved logging and error handling for merchant configurations

# 9.3.1
- PPI-944 - Fixes an issue, where payment status polling kept retrying with invalid transactions
- PPI-945 - Fixes an issue, where excluded products were not correctly shown in the Administration
- PPI-947 - Fixes an issue, where Smart Payment Buttons may have not been configurable
- PPI-948 - Removed the no longer supported Giropay payment method

# 9.3.0
- PPI-934 - Moved shipping tracking transmission to new PayPal API endpoints

# 9.2.0
- PPI-666 - Added information on invoices for Pay Upon Invoice payments
- PPI-924 - Added more explicit error messages for errors happening while checkout (e.g. postal code is missing)
- PPI-932 - Removed the no longer supported Sofort payment method
- PPI-936 - Fixes an issue, where Zettle may receive incorrect tax rates

# 9.1.1
- PPI-933 - Fixes an issue, where Apple Pay domains may not be registerable correctly

# 9.1.0
- PPI-850 - Added automated payment status polling for authorized and in-progress transactions
- PPI-862 - Added vaulting (save customer) to Venmo payment method
- PPI-863 - Added Apple Pay payment method
- PPI-863 - Added Google Pay payment method
- PPI-918 - Fixes an issue, where the refundable amount in the Administration was not shown correctly

# 9.0.3
- PPI-906 - Changed the express checkout button to make better use of remaining space for a more fitting layout
- PPI-914 - Fixes an issue, where orders with only digital products could not be processed and were wrongly categorized as physical products
- PPI-916 - Fixes an issue that may fail orders with landing page setting "Guest checkout"
- PPI-917 - Moved Pay Upon Invoice Disclaimer above Confirm button

# 9.0.2
- PPI-908 - Fixes an issue that sends an incorrect landing page setting to PayPal.

# 9.0.1
- PPI-896 - Changed the wording of the checkout Smart Payment Buttons from 'PayPal Checkout' to 'Pay with PayPal'.
- PPI-900 - Fixes an issue, where merchant integrations for payment methods are not loaded correctly
- PPI-904 - Fixes timestamp issue that caused the Shop to be inoperable with certain cache configurations

# 9.0.0
- PPI-830 - Added compatibility with Showpare 6.6 & Symfony 7
- PPI-857 - Added technical names for payment methods

# 8.0.0
- PPI-763 - Move "Pay later" banner underneath price display on product detail page
- PPI-779 - Added new Vaulting beta feature (save customer) for PayPal and Credit-/Debit card payments
- PPI-800 - Improved API struct structure & PayPal order building process
- PPI-827 - Added automated payment status polling for unconfirmed transactions
- PPI-831 - Improved management of payment status webhooks
- PPI-860 - Migrated to new card fields replacing hosted fields for Credit-/Debit card payments

# 7.4.0
- PPI-853 - Added option to add buyer country to "Pay Later" banners

# 7.3.2
- PPI-845 - Fixes an issue, where an order failed due to product names are too long
- PPI-849 - Added campaign and affiliate codes to express checkout
- PPI-855 - Added deprecation notice for Sofort payment method

# 7.3.1
- PPI-844 - Fixes an issue, where the installment banner was not toggleable on CMS product detail pages

# 7.3.0
- PPI-765 - "Pay later" banners can now be turned on and off more granular
- PPI-828 - Fixes an issue, where caching could interfere with the correct list of available payment methods

# 7.2.4
- PPI-818 - Added warning for possible unavailability of MyBank payment method
- PPI-820 - Fixes an issue, where the paid status was not possible to be set over webhooks
- PPI-826 - Express buttons are not displayed anymore, if the guest customer's double-opt-in feature is enabled.

# 7.2.3
- PPI-808 - Fixes an issue, where some currencies (HUF, JPY, TWD) were not transmitted correctly
- PPI-809 - Fixes an issue, where payment buttons have not the right color
- PPI-810 - More intuitive behavior of Administration settings
- PPI-811 - Fixes an issue, where credit cards with unavailable 3D Secure could not be processed
- PPI-812 - Fixes an issue, where Zettle sync could not be reset

# 7.2.2
- PPI-802 - Improved wording and default values in the Administration

# 7.2.1
- PPI-797 - Fixes an issue, where Zettle product sync could fail due to failing image sync

# 7.2.0
- PPI-769 - Add Pay Later button to Express checkout shortcut
- PPI-773 - The settings page is now structured in tabs
- PPI-788 - Move logging to default Symfony logging to improve in larger environments

# 7.1.0
- PPI-679 - Pay upon invoice payment details are now shown in the order details
- PPI-762 - Several payment methods are now available in non-PPCP markets
- PPI-767 - Added additional plugin information to transaction details

# 7.0.1
- PPI-757 - Fixes an issue, where payments of APM payment methods could be created as duplicates

# 7.0.0
- PPI-755 - Add Shopware 6.5 compatibility again

# 6.0.2
- PPI-753 - Fixes an issue, where template extensions were not possible in the meta block
- PPI-754 - Fixes an issue, where Storefront assets were not working with Shopware 6.4 (Shopware 6.5 supported has been temporarily dropped)

# 6.0.1
- PPI-751 - Fixes an issue with incompatibility with other plugins such as B2B suite and Customized Products

# 6.0.0
- PPI-430 - Improved processing of Zettle synchronisation
- PPI-659 - Added custom Storefront routes to adjust for missing Store API Client in Storefront in 6.5
- PPI-685, PPI-701, PPI-725 - Removed auto-hide for Smart Payment Buttons configuration in Administration
- PPI-731 - Compatibility with Shopware 6.5

# 5.4.6
- PPI-748 - Disable Trustly for now since PayPal has dropped API support
- PPI-749 - Fixes an issue, where the fallback button for credit card payments was not processed correctly

# 5.4.5
- PPI-734 - Fixes an issue, where some payment methods were not displayed in after order process
- PPI-720, PPI-741, PPI-743 - Fixes an issue, where the total tax amount was calculated incorrectly for net customers

# 5.4.4
- PPI-734 - Fixes an issue, where some payment methods were not displayed in after order process
- PPI-735 - Fixes an issue, where payment details were not shown on orders, where PayPal was not the first chosen payment method
- PPI-737 - Fixes an issue, where the order / payment details were not correctly transferred to PayPal

# 5.4.3
- PPI-654 - Fixes an issue, where Zettle sync errors were not displayed
- PPI-661 - Small performance improvements
- PPI-718 - Fixes an issue, where onboarding for specific Sales Channel configurations could not be completed
- PPI-733 - Fixes an issue, where the Sandbox flag was not correctly respected in specific Sales Channel configurations

# 5.4.2
- PPI-723 - Fixes an issue, where some APM payment methods did not work sometimes due to unannounced PayPal API changes
- PPI-724 - Fixes an issue, where the payment could fail if PayPal did not send full capture / authorization details

# 5.4.1
- PPI-716 - Fixes an error during the update if availability rules are still in use

# 5.4.0
- PPI-707 - Fixed issue where checking out with "Pay Later", "SEPA" and "Venmo" result in an error
- PPI-712 - Improved handling of payment method availability, removed availability rules
- PPI-713 - Improved 3D Secure handling in credit card payments

# 5.3.2
- PPI-709 - Fixed issue where PayPal was not installable, if the default language was neither English nor German

# 5.3.1
- PPI-672 - Fixed issue where captures could not always set transactions to paid
- PPI-681 - Fixed issue where Pay Later was not available for British and Australian customers
- PPI-681 - Fixed issue where Oxxo was not available for Mexican customers
- PPI-682 - Fixed issue with missing German translations in the administration
- PPI-684 - Improved spelling in the Administration
- PPI-688 - Fixed issue where the default carrier field in shipping methods was not always shown
- PPI-694 - Fixed issue where the default carrier field was shown in non-PayPal orders
- PPI-695 - Fixed issue ignoring excluded products per sales channel
- PPI-700 - Changed API URL to PayPal from `api.paypal.com` to `api-m.paypal.com` to increase performance
- PPI-702 - Fixed issue where payment details of non-PayPal-wallet payments were not always visible

# 5.3.0
- PPI-627 - Added new payment methods "Pay Later" and "Venmo"
- PPI-673 - Added automatic transmission of shipping tracking numbers to PayPal
- PPI-677 - Improved availability of payment methods in Administration
- PPI-678 - Fixed issue with Zettle Media URL field in the Administration

# 5.2.0
- PPI-625 - Added compatibility for new payment method overview of Shopware 6.4.14.0
- PPI-663 - Fix issue where taxes were not correctly calculated for net orders

# 5.1.2
- PPI-664 - Improved 3D Secure handling in credit card payments
- PPI-670 - Improved display of onboarding status in Administration

# 5.1.1
- PPI-657 - Cleaned up template `buy-widget-form` 

# 5.1.0
- PPI-611 - Added possibility to exclude products and dynamic product groups from PayPal & Express Checkout
- PPI-617 - Fix issue where payment method authorizations were not correctly shown for Sales Channel specific settings
- PPI-620 - Fix issue showing an incorrect webhook error message on saving settings without credentials 
- PPI-634 - Fix issue with shipping tax calculation for net customer groups
- PPI-635 - Fix issue with incorrect Pay Upon Invoice data display in invoices
- PPI-639 - The Sales Channel selection in the PayPal settings can now display more than 25 Sales Channels
- PPI-648 - Improved handling of Smart Payment Buttons, when the JS is not loaded fast enough
- PPI-649 - Fix issue where a partially refunded PayPal Plus payment was set to refunded in Shopware via Webhooks
- PPI-650 - Increase compatibility to Shopware 6.4.3.0

# 5.0.4
- PPI-642 - Fix issue where payment status was not correctly fetched with credit card payments

# 5.0.3
- PPI-624 - Improved error handling in after order process
- PPI-628 - Improves payment method choice if PayPal deems the buyer ineligible for certain methods
- PPI-629 - Fix issue with payment details not showing for APIv1 payments such as PayPal Plus

# 5.0.2
- PPI-621 - Fix issue with the payment method overview missing in Shopware 6.4.7.0 or lower
- PPI-623 - Fix an issue where a PayPal order cannot be created with discounts

# 5.0.1
- PPI-615 - Fix issue with missing German translations in the administration

# 5.0.0
- PPI-317 - Add separate credit card payment method
- PPI-385 - Add new Pay Upon Invoice payment method
- PPI-410 - Add separate APM payment methods
- PPI-418 - Add compatibility for PHP 8.1

# 4.1.1
- PPI-395 - Removed snippets for deprecated PayPal products

# 4.1.0
- PPI-344 - Fix issue with invalid phone numbers with API v1 payments
- PPI-346 - Fix rounding issues in payment capture modal in Administration
- PPI-350 - Enable after order process for unconfirmed payments with Shopware 6.4.4.0 or greater
- PPI-356 - Improved plugin extensibility
- PPI-366 - Improved payment error handling
- PPI-367 - Change wording of Sales Channel footer link text

# 4.0.0
- PPI-252 - Improved error handling for Webhooks
- PPI-327 - Improved data type structure
- PPI-343 - Fixes error when the customer account name differs from the shipping address name
- PPI-352 - Fixes issue with failing Zettle webhooks on POS sale

# 3.5.0
- PPI-5 - Implement Set PayPal as default payment method in First Run Wizard
- PPI-77 - Replaced snippets in administration by `global.defaults`
- PPI-126 - Improved error messaging for authorization errors in Zettle
- PPI-270 - Express Checkout does not create duplicate guest customers anymore
- PPI-293 - Improved PayPal script loading in Storefront
- PPI-330 - Improve Zettle decimal precision behavior
- PPI-334 - Fixes error on delayed capture in Administration
- PPI-339 - Fixes duplicate external link symbols in Administration

# 3.4.0
- PPI-228 - Added color white as choice for ECS and SPB buttons
- PPI-321 - Improve Zettle synchronisation behaviour
- PPI-322 - Improve removal of PayPal from available payment methods if credentials are invalid
- PPI-323 - Fix issues with loading spinner with Smart Payment Buttons
- PPI-329 - Fix rounding issues in PayPal API v2 requests

# 3.3.1
- PPI-316 - Fix issue when changing default language after plugin install

# 3.3.0
- PPI-219 - Disable PayPal on Carts with total price of 0
- PPI-227 - Add possibility to add a suffix to the order number sent to PayPal
- PPI-281 - Improved storefront behaviour of Express Checkout & Smart Payment Button cancellations and errors
- PPI-287 - Fix issue where Express button was not shown in CMS buy box elements after variant switching
- PPI-289 - Fix issue where the Express Checkout button could be visible to logged in customers
- PPI-304 - Fix Smart Payment Buttons being visible with cart errors

# 3.2.1
- PPI-279, PPI-297 - Extends the partner referral API
- PPI-290 - Improved extensibility
- PPI-295 - Fix order details in administration not showing complete page
- PPI-296 - Improved display of APMs in Footer
- PPI-298 - Fix issues with loading spinner with Smart Payment Buttons
- PPI-300 - It is no longer possible to select other payment methods on Express Checkout

# 3.2.0
- PPI-262 - Fix issue where Express button was not shown in CMS buy box elements
- PPI-271 - Fix an issue where updated settings did not correctly invalidate cache
- PPI-277 - Fix issue where Express checkout failed on changes on confirm page
- PPI-273 - Order number prefix is now always sent correctly
- PPI-282 - Fix incompatibility with the Sendcloud plugin
- PPI-283 - Removed extra confirm step with Smart Payment Buttons which correctly enables Alternative Payment Methods

# 3.1.0
- PPI-246 - Added option for advanced logging
- PPI-251 - Fix authorized transaction state on delayed payment collection
- PPI-276 - Fix multiple unnecessary requests in the background with PayPal Plus

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
