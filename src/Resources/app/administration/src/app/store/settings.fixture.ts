import type * as PayPal from 'SwagPayPal/types';

const Default = {
    'SwagPayPal.settings.sandbox': false,
    'SwagPayPal.settings.intent': 'CAPTURE',
    'SwagPayPal.settings.submitCart': true,
    'SwagPayPal.settings.landingPage': 'NO_PREFERENCE',
    'SwagPayPal.settings.sendOrderNumber': true,
    'SwagPayPal.settings.ecsDetailEnabled': true,
    'SwagPayPal.settings.ecsCartEnabled': true,
    'SwagPayPal.settings.ecsOffCanvasEnabled': true,
    'SwagPayPal.settings.ecsLoginEnabled': true,
    'SwagPayPal.settings.ecsListingEnabled': false,
    'SwagPayPal.settings.ecsButtonColor': 'gold',
    'SwagPayPal.settings.ecsButtonShape': 'sharp',
    'SwagPayPal.settings.ecsShowPayLater': true,
    'SwagPayPal.settings.ecsButtonLanguageIso': null,

    'SwagPayPal.settings.spbButtonColor': 'gold',
    'SwagPayPal.settings.spbButtonShape': 'sharp',
    'SwagPayPal.settings.spbButtonLanguageIso': null,
    'SwagPayPal.settings.spbShowPayLater': true,
    'SwagPayPal.settings.spbCheckoutEnabled': true,
    'SwagPayPal.settings.spbAlternativePaymentMethodsEnabled': false,

    'SwagPayPal.settings.installmentBannerDetailPageEnabled': true,
    'SwagPayPal.settings.installmentBannerCartEnabled': true,
    'SwagPayPal.settings.installmentBannerOffCanvasCartEnabled': true,
    'SwagPayPal.settings.installmentBannerLoginPageEnabled': true,
    'SwagPayPal.settings.installmentBannerFooterEnabled': true,

    'SwagPayPal.settings.vaultingEnabledWallet': false,
    'SwagPayPal.settings.vaultingEnabledACDC': false,
    'SwagPayPal.settings.vaultingEnabledVenmo': false,

    'SwagPayPal.settings.acdcForce3DS': false,

    'SwagPayPal.settings.excludedProductIds': [],
    'SwagPayPal.settings.excludedProductStreamIds': [],

    'SwagPayPal.settings.crossBorderMessagingEnabled': false,
    'SwagPayPal.settings.crossBorderBuyerCountry': null,
} satisfies PayPal.SystemConfig;

const All = {
    ...Default,
    'SwagPayPal.settings.clientId': '',
    'SwagPayPal.settings.clientSecret': '',
    'SwagPayPal.settings.clientIdSandbox': '',
    'SwagPayPal.settings.clientSecretSandbox': '',
    'SwagPayPal.settings.merchantPayerId': '',
    'SwagPayPal.settings.merchantPayerIdSandbox': '',

    'SwagPayPal.settings.webhookId': '',
    'SwagPayPal.settings.webhookExecuteToken': '',
    'SwagPayPal.settings.brandName': '',
    'SwagPayPal.settings.orderNumberPrefix': '',
    'SwagPayPal.settings.orderNumberSuffix': '',

    'SwagPayPal.settings.puiCustomerServiceInstructions': '',

    'SwagPayPal.settings.vaultingEnabled': false,
    'SwagPayPal.settings.vaultingEnableAlways': false,
} satisfies Required<PayPal.SystemConfig>;

const WithCredentials = {
    ...Default,
    'SwagPayPal.settings.clientId': 'some-client-id',
    'SwagPayPal.settings.clientSecret': 'some-client-secret',
    'SwagPayPal.settings.merchantPayerId': 'some-merchant-payer-id',
};

const WithSandboxCredentials = {
    ...Default,
    'SwagPayPal.settings.clientIdSandbox': 'some-client-id-sandbox',
    'SwagPayPal.settings.clientSecretSandbox': 'some-client-secret-sandbox',
    'SwagPayPal.settings.merchantPayerIdSandbox': 'some-merchant-payer-id-sandbox',
};

export default { Default, All, WithCredentials, WithSandboxCredentials };
