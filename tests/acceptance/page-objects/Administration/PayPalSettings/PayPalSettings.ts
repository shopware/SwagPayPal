import type { Page, Locator } from '@shopware-ag/acceptance-test-suite';
import { PageObject } from '@shopware-ag/acceptance-test-suite';

export class PayPalSettings implements PageObject {
    public readonly page: Page;

    public readonly saveButton: Locator;

    public readonly generalTab: Locator;
    public readonly storefrontTab: Locator;
    public readonly advancedTab: Locator;

    // general tab
    public readonly credentialsCard: Locator;
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

        // Most cards of Shopware 6.5 have no distinct class, so they are located by their title
        this.credentialsCard = this.getCardByTitle('API credentials');
        this.behaviourCard = this.getCardByTitle('Behaviour');
        this.vaultingCard = page.locator('.swag-paypal-vaulting');
        this.acdcCard = this.getCardByTitle('Credit- or debit card');
        this.puiCard = this.getCardByTitle('Pay upon invoice');

        this.expressCard = this.getCardByTitle('Express Checkout Shortcut');
        this.payLaterCard = this.getCardByTitle('\'Pay Later\' banner');
        this.spbCard = this.getCardByTitle('Smart Payment Buttons');

        this.webhookCard = page.locator('.swag-paypal-webhook');
        this.crossBorderMessagingCard = page.locator('.swag-paypal-cross-border');
    }

    url() {
        return '#/swag/paypal/index';
    }

    private getCardByTitle(title: string): Locator {
        return this.page.locator('.sw-card', {
            has: this.page.locator('.sw-card__title', { hasText: title }),
        });
    }
}
