import { mergeTests } from '@shopware-ag/acceptance-test-suite';
import { SetStandardCarrier, type SetStandardCarrierTask } from './ShopAdmin/Shipping/SetStandardCarrier';

export interface ShopAdminTasks {
    SetStandardCarrier: SetStandardCarrierTask
}

export const test = mergeTests(
    SetStandardCarrier,
);
