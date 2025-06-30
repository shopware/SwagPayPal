import type { Page, Locator } from '@shopware-ag/acceptance-test-suite';
import { PageObject } from '@shopware-ag/acceptance-test-suite';

export class PaymentListing implements PageObject {
    public readonly page: Page;

    public readonly methodCard: Locator;
    public readonly methodListing: Locator;
    public readonly connectionStatus: Locator;
    public readonly merchantInformationCard: Locator;
    public readonly merchantInformation: Locator;
    public readonly liveOnboardingButton: Locator;
    public readonly sandboxOnboardingButton: Locator;
    public readonly sandboxToggle: Locator;

    constructor(page: Page) {
        this.page = page;

        this.methodCard = page.locator('.swag-paypal-method-card');
        this.methodListing = page.locator('.swag-paypal-method-card__listing');
        this.connectionStatus = this.methodCard.locator('.swag-paypal-method-card__status');
        this.merchantInformationCard = this.methodCard.locator('.swag-paypal-merchant-information');
        this.merchantInformation = this.merchantInformationCard.locator('.swag-paypal-merchant-information__email');

        this.liveOnboardingButton = this.methodCard.locator('.swag-paypal-onboarding-button.is--live');
        this.sandboxOnboardingButton = this.methodCard.locator('.swag-paypal-onboarding-button.is--sandbox');
        this.sandboxToggle = this.methodCard.getByRole('checkbox', { name: 'SwagPayPal.settings.sandbox' });
    }

    getMethodByName(name: string): Record<'method' | 'methodToggle' | 'methodStatus', Locator> {
        const methodName = this.methodCard.locator('swag-paypal-payment-method__name', { hasText: name });
        const method = this.methodCard.locator('swag-paypal-payment-method', { has: methodName });
        const methodToggle = method.getByRole('checkbox');
        const methodStatus = method.locator('.swag-paypal-payment-method__status-label');

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
