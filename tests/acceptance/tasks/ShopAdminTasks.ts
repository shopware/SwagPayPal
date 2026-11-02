import { mergeTests } from '@shopware-ag/acceptance-test-suite';
import { AttachPayPalCapture, type AttachPayPalCaptureTask } from './ShopAdmin/Order/AttachPayPalCapture';
import { CaptureSandboxPayPalOrder, type CaptureSandboxPayPalOrderTask } from './ShopAdmin/Order/CaptureSandboxPayPalOrder';
import { ReadOrderTransactionState, type ReadOrderTransactionStateTask } from './ShopAdmin/Order/ReadOrderTransactionState';
import { ReadPaymentMethodId, type ReadPaymentMethodIdTask } from './ShopAdmin/Payment/ReadPaymentMethodId';
import { ExecuteZettleWebhook, type ExecuteZettleWebhookTask } from './ShopAdmin/Pos/ExecuteZettleWebhook';
import { OpenZettleSalesChannel, type OpenZettleSalesChannelTask } from './ShopAdmin/Pos/OpenZettleSalesChannel';
import { ReplaceWebhookToken, type ReplaceWebhookTokenTask } from './ShopAdmin/Settings/ReplaceWebhookToken';
import { SetStandardCarrier, type SetStandardCarrierTask } from './ShopAdmin/Shipping/SetStandardCarrier';
import { ExecutePayPalWebhook, type ExecutePayPalWebhookTask } from './ShopAdmin/Webhook/ExecutePayPalWebhook';

export interface ShopAdminTasks {
    AttachPayPalCapture: AttachPayPalCaptureTask
    CaptureSandboxPayPalOrder: CaptureSandboxPayPalOrderTask
    ExecutePayPalWebhook: ExecutePayPalWebhookTask
    ExecuteZettleWebhook: ExecuteZettleWebhookTask
    OpenZettleSalesChannel: OpenZettleSalesChannelTask
    ReadOrderTransactionState: ReadOrderTransactionStateTask
    ReadPaymentMethodId: ReadPaymentMethodIdTask
    ReplaceWebhookToken: ReplaceWebhookTokenTask
    SetStandardCarrier: SetStandardCarrierTask
}

export const test = mergeTests(
    AttachPayPalCapture,
    CaptureSandboxPayPalOrder,
    ExecutePayPalWebhook,
    ExecuteZettleWebhook,
    OpenZettleSalesChannel,
    ReadOrderTransactionState,
    ReadPaymentMethodId,
    ReplaceWebhookToken,
    SetStandardCarrier,
);
