import { test } from '@fixtures/AcceptanceTest';
import { expect } from '@shopware-ag/acceptance-test-suite';

test('Reset PayPal Settings', {}, async ({ AdminApiContext }) => {
    const resetResponse = await AdminApiContext.post('./_action/paypal/save-settings', {
        data: {
            null: {
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
                'SwagPayPal.settings.spbCheckoutEnabled': true,
                'SwagPayPal.settings.spbAlternativePaymentMethodsEnabled': false,
                'SwagPayPal.settings.spbButtonColor': 'gold',
                'SwagPayPal.settings.spbButtonShape': 'sharp',
                'SwagPayPal.settings.spbShowPayLater': false,
                'SwagPayPal.settings.installmentBannerDetailPageEnabled': true,
                'SwagPayPal.settings.installmentBannerCartEnabled': true,
                'SwagPayPal.settings.installmentBannerOffCanvasCartEnabled': true,
                'SwagPayPal.settings.installmentBannerLoginPageEnabled': true,
                'SwagPayPal.settings.installmentBannerFooterEnabled': true,
                'SwagPayPal.settings.puiCustomerServiceInstructions': 'Details zum Kundenservice finden Sie auf unserer Webseite',
                'SwagPayPal.settings.acdcForce3DS': false,
                'SwagPayPal.settings.excludedProductIds': [],
                'SwagPayPal.settings.excludedProductStreamIds': [],
                'SwagPayPal.settings.vaultingEnabledACDC': false,
                'SwagPayPal.settings.vaultingEnabledWallet': false,
                'SwagPayPal.settings.vaultingEnabledVenmo': false,
                'SwagPayPal.settings.crossBorderMessagingEnabled': false,
                'SwagPayPal.settings.crossBorderBuyerCountry': null,
            },
        },
    });

    expect(resetResponse.ok()).toBeTruthy();
});
