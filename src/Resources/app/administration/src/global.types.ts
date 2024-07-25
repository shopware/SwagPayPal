import type Repository from 'src/core/data/repository.data';
import type Criteria from 'src/core/data/criteria.data';
import type { Entity } from 'src/types/entity';
import type { PropType as TPropType } from 'vue';
import type SwagPaypalNotificationMixin from './mixin/swag-paypal-notification.mixin';
import type SwagPaypalCredentialsLoaderMixin from './mixin/swag-paypal-credentials-loader.mixin';
import type SwagPaypalPosCatchErrorMixin from './mixin/swag-paypal-pos-catch-error.mixin';
import type SwagPaypalPosLogLabelMixin from './mixin/swag-paypal-pos-log-label.mixin';
import type SwagPayPalApiCredentialsService from './core/service/api/swag-paypal-api-credentials.service';
import type SwagPayPalDisputeApiService from './core/service/api/swag-paypal-dispute.api.service';
import type SwagPayPalOrderService from './core/service/api/swag-paypal-order.service';
import type SwagPaypalPaymentMethodService from './core/service/api/swag-paypal-payment-method.service';
import type SwagPayPalPaymentService from './core/service/api/swag-paypal-payment.service';
import type SwagPayPalPosSettingApiService from './core/service/api/swag-paypal-pos-setting.api.service';
import type SwagPayPalPosWebhookRegisterService from './core/service/api/swag-paypal-pos-webhook-register.service';
import type SwagPayPalPosApiService from './core/service/api/swag-paypal-pos.api.service';
import type SwagPayPalWebhookService from './core/service/api/swag-paypal-webhook.service';

declare global {
    type TEntity<T extends keyof EntitySchema.Entities> = Entity<T>;
    type TEntityCollection<T extends keyof EntitySchema.Entities> = EntitySchema.EntityCollection<T>;
    type TRepository<T extends keyof EntitySchema.Entities> = Repository<T>;
    type TCriteria = Criteria;
    type PropType<T> = TPropType<T>;

    interface MixinContainer {
        'swag-paypal-credentials-loader': typeof SwagPaypalCredentialsLoaderMixin;
        'swag-paypal-notification': typeof SwagPaypalNotificationMixin;
        'swag-paypal-pos-catch-error': typeof SwagPaypalPosCatchErrorMixin;
        'swag-paypal-pos-log-label': typeof SwagPaypalPosLogLabelMixin;
    }

    interface ServiceContainer {
        SwagPayPalApiCredentialsService: SwagPayPalApiCredentialsService;
        SwagPayPalPosSettingApiService: SwagPayPalPosSettingApiService;
        SwagPayPalPosApiService: SwagPayPalPosApiService;
        SwagPayPalPosWebhookRegisterService: SwagPayPalPosWebhookRegisterService;
        SwagPayPalWebhookService: SwagPayPalWebhookService;
        SwagPayPalPaymentService: SwagPayPalPaymentService;
        SwagPayPalOrderService: SwagPayPalOrderService;
        SwagPaypalPaymentMethodService: SwagPaypalPaymentMethodService;
        SwagPayPalDisputeApiService: SwagPayPalDisputeApiService;
    }
}

declare module '@vue/runtime-core' {
    interface ComponentCustomProperties {
        $super: (name: string) => $TSFixMe;
    }
}
