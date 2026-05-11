import { test as base } from '@playwright/test';
import { OrderDetail } from './Administration/Order/OrderDetail';
import { PaymentListing } from './Administration/Payment/PaymentListing';
import { PayPalDisputesDetail } from './Administration/PayPalDisputes/PayPalDisputesDetail';
import { PayPalDisputesListing } from './Administration/PayPalDisputes/PayPalDisputesListing';
import { PayPalSettings } from './Administration/PayPalSettings/PayPalSettings';
import { ShippingDetail } from './Administration/Shipping/ShippingDetail';

export interface AdminPageTypes {
    AdminOrderDetail: OrderDetail
    AdminPaymentListing: PaymentListing
    AdminPayPalDisputesDetail: PayPalDisputesDetail
    AdminPayPalDisputesListing: PayPalDisputesListing
    AdminPayPalSettings: PayPalSettings
    AdminShippingDetail: ShippingDetail
}

export const test = base.extend<FixtureTypes>({
    AdminOrderDetail: async ({ AdminPage }, use) => {
        await use(new OrderDetail(AdminPage));
    },

    AdminPaymentListing: async ({ AdminPage }, use) => {
        await use(new PaymentListing(AdminPage));
    },

    AdminPayPalDisputesDetail: async ({ AdminPage }, use) => {
        await use(new PayPalDisputesDetail(AdminPage));
    },

    AdminPayPalDisputesListing: async ({ AdminPage }, use) => {
        await use(new PayPalDisputesListing(AdminPage));
    },

    AdminPayPalSettings: async ({ AdminPage }, use) => {
        await use(new PayPalSettings(AdminPage));
    },

    AdminShippingDetail: async ({ AdminPage }, use) => {
        await use(new ShippingDetail(AdminPage));
    },
});
