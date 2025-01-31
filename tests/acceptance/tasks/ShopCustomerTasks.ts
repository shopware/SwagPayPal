import { mergeTests } from '@shopware-ag/acceptance-test-suite';
import { FillPaymentACDC, type FillPaymentACDCTask } from './ShopCustomer/Checkout/FillPaymentACDC';
import { SelectPaymentOption, type SelectPaymentOptionTask } from './ShopCustomer/Checkout/SelectPaymentOption';

export interface ShopCustomerTasks {
    FillPaymentACDC: FillPaymentACDCTask
    SelectPaymentOption: SelectPaymentOptionTask
}

export const test = mergeTests(
    FillPaymentACDC,
    SelectPaymentOption,
);
