import type { SYSTEM_CONFIG, LANDING_PAGES, BUTTON_COLORS, BUTTON_SHAPES, INTENTS, COUNTRY_OVERRIDES } from 'SwagPayPal/constant/swag-paypal-settings.constant';

// @todo - Keys should be from SYSTEM_CONFIG
export declare type SystemConfig = {
    'SwagPayPal.settings.clientId'?: string;
    'SwagPayPal.settings.clientSecret'?: string;
    'SwagPayPal.settings.clientIdSandbox'?: string;
    'SwagPayPal.settings.clientSecretSandbox'?: string;
    'SwagPayPal.settings.merchantPayerId'?: string;
    'SwagPayPal.settings.merchantPayerIdSandbox'?: string;
    'SwagPayPal.settings.sandbox'?: boolean;
    'SwagPayPal.settings.intent'?: typeof INTENTS[number];
    'SwagPayPal.settings.submitCart'?: boolean;
    'SwagPayPal.settings.webhookId'?: string;
    'SwagPayPal.settings.webhookExecuteToken'?: string;
    'SwagPayPal.settings.brandName'?: string;
    'SwagPayPal.settings.landingPage'?: typeof LANDING_PAGES[number];
    'SwagPayPal.settings.sendOrderNumber'?: boolean;
    'SwagPayPal.settings.orderNumberPrefix'?: string;
    'SwagPayPal.settings.orderNumberSuffix'?: string;
    'SwagPayPal.settings.ecsDetailEnabled'?: boolean;
    'SwagPayPal.settings.ecsCartEnabled'?: boolean;
    'SwagPayPal.settings.ecsOffCanvasEnabled'?: boolean;
    'SwagPayPal.settings.ecsLoginEnabled'?: boolean;
    'SwagPayPal.settings.ecsListingEnabled'?: boolean;
    'SwagPayPal.settings.ecsButtonColor'?: typeof BUTTON_COLORS[number];
    'SwagPayPal.settings.ecsButtonShape'?: typeof BUTTON_SHAPES[number];
    'SwagPayPal.settings.ecsButtonLanguageIso'?: string | null;

    'SwagPayPal.settings.ecsShowPayLater'?: boolean;
    'SwagPayPal.settings.spbButtonColor'?: typeof BUTTON_COLORS[number];
    'SwagPayPal.settings.spbButtonShape'?: typeof BUTTON_SHAPES[number];
    'SwagPayPal.settings.spbButtonLanguageIso'?: string | null;
    'SwagPayPal.settings.acdcForce3DS'?: boolean;
    'SwagPayPal.settings.puiCustomerServiceInstructions'?: string;
    'SwagPayPal.settings.installmentBannerDetailPageEnabled'?: boolean;
    'SwagPayPal.settings.installmentBannerCartEnabled'?: boolean;
    'SwagPayPal.settings.installmentBannerOffCanvasCartEnabled'?: boolean;
    'SwagPayPal.settings.installmentBannerLoginPageEnabled'?: boolean;
    'SwagPayPal.settings.installmentBannerFooterEnabled'?: boolean;
    'SwagPayPal.settings.excludedProductIds'?: string[];
    'SwagPayPal.settings.excludedProductStreamIds'?: string[];
    'SwagPayPal.settings.spbShowPayLater'?: boolean;
    'SwagPayPal.settings.spbCheckoutEnabled'?: boolean;
    'SwagPayPal.settings.spbAlternativePaymentMethodsEnabled'?: boolean;
    'SwagPayPal.settings.vaultingEnabled'?: boolean;
    'SwagPayPal.settings.vaultingEnableAlways'?: boolean;
    'SwagPayPal.settings.vaultingEnabledWallet'?: boolean;
    'SwagPayPal.settings.vaultingEnabledACDC'?: boolean;
    'SwagPayPal.settings.vaultingEnabledVenmo'?: boolean;

    'SwagPayPal.settings.crossBorderMessagingEnabled'?: boolean;
    'SwagPayPal.settings.crossBorderBuyerCountry'?: typeof COUNTRY_OVERRIDES[number] | null;
};

/**
 * @private
 */
export const SystemConfigDefinition: Record<SYSTEM_CONFIG, 'string' | 'password' | 'boolean' | 'array'> = {
    'SwagPayPal.settings.clientId': 'string',
    'SwagPayPal.settings.clientSecret': 'password',
    'SwagPayPal.settings.clientIdSandbox': 'string',
    'SwagPayPal.settings.clientSecretSandbox': 'password',
    'SwagPayPal.settings.merchantPayerId': 'string',
    'SwagPayPal.settings.merchantPayerIdSandbox': 'string',
    'SwagPayPal.settings.sandbox': 'boolean',
    'SwagPayPal.settings.intent': 'string',
    'SwagPayPal.settings.submitCart': 'boolean',
    'SwagPayPal.settings.webhookId': 'string',
    'SwagPayPal.settings.webhookExecuteToken': 'string',
    'SwagPayPal.settings.brandName': 'string',
    'SwagPayPal.settings.landingPage': 'string',
    'SwagPayPal.settings.sendOrderNumber': 'boolean',
    'SwagPayPal.settings.orderNumberPrefix': 'string',
    'SwagPayPal.settings.orderNumberSuffix': 'string',
    'SwagPayPal.settings.ecsDetailEnabled': 'boolean',
    'SwagPayPal.settings.ecsCartEnabled': 'boolean',
    'SwagPayPal.settings.ecsOffCanvasEnabled': 'boolean',
    'SwagPayPal.settings.ecsLoginEnabled': 'boolean',
    'SwagPayPal.settings.ecsListingEnabled': 'boolean',
    'SwagPayPal.settings.ecsButtonColor': 'string',
    'SwagPayPal.settings.ecsButtonShape': 'string',
    'SwagPayPal.settings.ecsButtonLanguageIso': 'string',

    'SwagPayPal.settings.ecsShowPayLater': 'boolean',
    'SwagPayPal.settings.spbButtonColor': 'string',
    'SwagPayPal.settings.spbButtonShape': 'string',
    'SwagPayPal.settings.spbButtonLanguageIso': 'string',
    'SwagPayPal.settings.acdcForce3DS': 'boolean',
    'SwagPayPal.settings.puiCustomerServiceInstructions': 'string',
    'SwagPayPal.settings.installmentBannerDetailPageEnabled': 'boolean',
    'SwagPayPal.settings.installmentBannerCartEnabled': 'boolean',
    'SwagPayPal.settings.installmentBannerOffCanvasCartEnabled': 'boolean',
    'SwagPayPal.settings.installmentBannerLoginPageEnabled': 'boolean',
    'SwagPayPal.settings.installmentBannerFooterEnabled': 'boolean',
    'SwagPayPal.settings.excludedProductIds': 'array',
    'SwagPayPal.settings.excludedProductStreamIds': 'array',
    'SwagPayPal.settings.spbShowPayLater': 'boolean',
    'SwagPayPal.settings.spbCheckoutEnabled': 'boolean',
    'SwagPayPal.settings.spbAlternativePaymentMethodsEnabled': 'boolean',
    'SwagPayPal.settings.vaultingEnabled': 'boolean',
    'SwagPayPal.settings.vaultingEnableAlways': 'boolean',
    'SwagPayPal.settings.vaultingEnabledWallet': 'boolean',
    'SwagPayPal.settings.vaultingEnabledACDC': 'boolean',
    'SwagPayPal.settings.vaultingEnabledVenmo': 'boolean',

    'SwagPayPal.settings.crossBorderMessagingEnabled': 'boolean',
    'SwagPayPal.settings.crossBorderBuyerCountry': 'string',
};
