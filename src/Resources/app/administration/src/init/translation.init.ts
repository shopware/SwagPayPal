/**
 * @deprecated tag:v11.0.0 - With Shopware v6.8.0 - `translation.init.ts` will be removed to use automatic language loading with language layer support
 */
import deGeneralSnippets from '../app/snippet/de.json';
import enGeneralSnippets from '../app/snippet/en.json';

import deExtensionSnippets from '../module/extension/snippet/de.json';
import enExtensionSnippets from '../module/extension/snippet/en.json';

import deFrwSnippets from '../module/extension/sw-first-run-wizard/snippets/de.json';
import enFrwSnippets from '../module/extension/sw-first-run-wizard/snippets/en.json';

import deShippingSnippets from '../module/extension/sw-settings-shipping/snippets/de.json';
import enShippingSnippets from '../module/extension/sw-settings-shipping/snippets/en.json';

import deDisputesSnippets from '../module/swag-paypal-disputes/snippet/de.json';
import enDisputesSnippets from '../module/swag-paypal-disputes/snippet/en.json';

import deMethodSnippets from '../module/swag-paypal-method/snippet/de.json';
import enMethodSnippets from '../module/swag-paypal-method/snippet/en.json';

import dePaymentSnippets from '../module/swag-paypal-payment/snippet/de.json';
import enPaymentSnippets from '../module/swag-paypal-payment/snippet/en.json';

import dePosSnippets from '../module/swag-paypal-pos/snippet/de.json';
import enPosSnippets from '../module/swag-paypal-pos/snippet/en.json';

import deSettingsSnippets from '../module/swag-paypal-settings/snippet/de.json';
import enSettingsSnippets from '../module/swag-paypal-settings/snippet/en.json';

Shopware.Locale.extend('de-DE', deFrwSnippets);
Shopware.Locale.extend('en-GB', enFrwSnippets);

Shopware.Locale.extend('de-DE', deExtensionSnippets);
Shopware.Locale.extend('en-GB', enExtensionSnippets);

Shopware.Locale.extend('de-DE', deGeneralSnippets);
Shopware.Locale.extend('en-GB', enGeneralSnippets);

Shopware.Locale.extend('de-DE', deShippingSnippets);
Shopware.Locale.extend('en-GB', enShippingSnippets);

Shopware.Locale.extend('de-DE', deDisputesSnippets);
Shopware.Locale.extend('en-GB', enDisputesSnippets);

Shopware.Locale.extend('de-DE', deMethodSnippets);
Shopware.Locale.extend('en-GB', enMethodSnippets);

Shopware.Locale.extend('de-DE', dePaymentSnippets);
Shopware.Locale.extend('en-GB', enPaymentSnippets);

Shopware.Locale.extend('de-DE', dePosSnippets);
Shopware.Locale.extend('en-GB', enPosSnippets);

Shopware.Locale.extend('de-DE', deSettingsSnippets);
Shopware.Locale.extend('en-GB', enSettingsSnippets);

