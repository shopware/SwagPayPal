import type { Page, Locator } from '@shopware-ag/acceptance-test-suite';
import { AdminPageObjects } from '@shopware-ag/acceptance-test-suite';

export class ShippingDetail extends AdminPageObjects.ShippingDetail {
    public readonly standardShippingCarrierCard: Locator;
    public readonly standardShippingCarrierInput: Locator;
    public readonly standardShippingCarrierList: Locator;
    public readonly standardShippingCarrierItem: Locator;

    constructor(page: Page) {
        super(page);

        this.standardShippingCarrierCard = page.locator('.swag-paypal-settings-shipping-carrier');
        this.standardShippingCarrierInput = this.standardShippingCarrierCard.getByLabel('Carrier code');
        this.standardShippingCarrierList = this.standardShippingCarrierCard.locator('.sw-single-select__selection');
        this.standardShippingCarrierItem = this.page.locator('.sw-select-result');
    }

    getCarrierListResult(carrierName: string): Locator {
        return this.page.locator('.sw-select-result__result-item-text').filter({ hasText: carrierName });
    }
}
