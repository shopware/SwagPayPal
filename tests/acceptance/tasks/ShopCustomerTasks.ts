import { mergeTests } from '@shopware-ag/acceptance-test-suite';
import { FillPaymentACDC, type FillPaymentACDCTask } from './ShopCustomer/Checkout/FillPaymentACDC';
import { RestorePayPalContext, type RestorePayPalContextTask } from './ShopCustomer/Checkout/RestorePayPalContext';
import { SelectPaymentOption, type SelectPaymentOptionTask } from './ShopCustomer/Checkout/SelectPaymentOption';

export interface ShopCustomerTasks {
    FillPaymentACDC: FillPaymentACDCTask
    RestorePayPalContext: RestorePayPalContextTask
    SelectPaymentOption: SelectPaymentOptionTask
}

export const test = mergeTests(
    FillPaymentACDC,
    RestorePayPalContext,
    SelectPaymentOption,
);
