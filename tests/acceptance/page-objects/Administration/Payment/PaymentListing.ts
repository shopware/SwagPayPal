import type { Page, Locator } from '@shopware-ag/acceptance-test-suite';
import { PageObject } from '@shopware-ag/acceptance-test-suite';

export class PaymentListing implements PageObject {
    public readonly page: Page;

    public readonly methodCard: Locator;
    public readonly methodListing: Locator;
    public readonly merchantInformation: Locator;
    public readonly liveOnboardingButton: Locator;
    public readonly sandboxOnboardingButton: Locator;
    public readonly sandboxToggle: Locator;

    constructor(page: Page) {
        this.page = page;

        this.methodCard = page.locator('.sw-card', { has: page.locator('.swag-paypal-checkout') });
        this.methodListing = this.methodCard.locator('.swag-paypal-checkout-payment-method_container');
        this.merchantInformation = this.methodCard.locator('.swag-paypal-checkout__merchant-information');

        this.liveOnboardingButton = this.methodCard.locator('[data-paypal-onboard-complete="onboardingCallbackLive"]');
        this.sandboxOnboardingButton = this.methodCard.locator('.swag-paypal-checkout__button-onboarding-sandbox');
        this.sandboxToggle = this.methodCard.locator('.swag-paypal-checkout__sandbox-toggle').getByRole('checkbox');
    }

    getMethodByName(name: string): Record<'method' | 'methodToggle' | 'methodStatus', Locator> {
        const methodName = this.methodListing.locator('.swag-paypal-checkout-method__name', { hasText: name });
        const method = this.methodListing.locator('.swag-paypal-checkout-method', { has: methodName });
        const methodToggle = method.getByRole('checkbox');
        const methodStatus = method.locator('.swag-plugin-box-with-onboarding__status-label');

        return {
            method,
            methodToggle,
            methodStatus,
        };
    }

    url() {
        return '#/sw/settings/payment/overview';
    }
}
