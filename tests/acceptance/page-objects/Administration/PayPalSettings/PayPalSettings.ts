import type { Page, Locator } from '@shopware-ag/acceptance-test-suite';
import { PageObject } from '@shopware-ag/acceptance-test-suite';

export class PayPalSettings implements PageObject {
    public readonly page: Page;

    public readonly saveButton: Locator;

    public readonly generalTab: Locator;
    public readonly storefrontTab: Locator;
    public readonly advancedTab: Locator;

    // general tab
    public readonly liveCredentialsCard: Locator;
    public readonly sandboxCredentialsCard: Locator;
    public readonly behaviourCard: Locator;
    public readonly vaultingCard: Locator;
    public readonly acdcCard: Locator;
    public readonly puiCard: Locator;

    // storefront tab
    public readonly expressCard: Locator;
    public readonly payLaterCard: Locator;
    public readonly spbCard: Locator;

    // advanced tab
    public readonly webhookCard: Locator;
    public readonly crossBorderMessagingCard: Locator;

    constructor(page: Page) {
        this.page = page;
        this.saveButton = page.getByRole('button', { name: 'Save' });

        this.generalTab = page.getByRole('link', { name: 'General' });
        this.storefrontTab = page.getByRole('link', { name: 'Storefront' });
        this.advancedTab = page.getByRole('link', { name: 'Advanced' });

        this.liveCredentialsCard = page.locator('.swag-paypal-settings-live-credentials');
        this.sandboxCredentialsCard = page.locator('.swag-paypal-settings-sandbox-credentials');
        this.behaviourCard = page.locator('.swag-paypal-settings-behavior');
        this.vaultingCard = page.locator('.swag-paypal-settings-vaulting');
        this.acdcCard = page.locator('.swag-paypal-settings-acdc');
        this.puiCard = page.locator('.swag-paypal-settings-pui');

        this.expressCard = page.locator('.swag-paypal-settings-express');
        this.payLaterCard = page.locator('.swag-paypal-settings-installment');
        this.spbCard = page.locator('.swag-paypal-settings-spb');

        this.webhookCard = page.locator('.swag-paypal-settings-webhook');
        this.crossBorderMessagingCard = page.locator('.swag-paypal-settings-cross-border');
    }

    url() {
        return '#/swag/paypal/settings/index';
    }
}
