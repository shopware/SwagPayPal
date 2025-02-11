import type Repository from 'src/core/data/repository.data';
import type Criteria from 'src/core/data/criteria.data';
import type { Entity } from 'SwagPayPal/types/entity';
import type { PropType as TPropType } from 'vue';
import type SwagPaypalNotificationMixin from './mixin/swag-paypal-notification.mixin';
import type SwagPaypalPosCatchErrorMixin from './mixin/swag-paypal-pos-catch-error.mixin';
import type SwagPaypalPosLogLabelMixin from './mixin/swag-paypal-pos-log-label.mixin';
import type SwagPaypalSettingsMixin from './mixin/swag-paypal-settings.mixin';
import type SwagPaypalMerchantInformationMixin from './mixin/swag-paypal-merchant-information.mixin';
import type SwagPayPalDisputeApiService from './core/service/api/swag-paypal-dispute.api.service';
import type SwagPayPalOrderService from './core/service/api/swag-paypal-order.service';
import type SwagPaypalPaymentMethodService from './core/service/api/swag-paypal-payment-method.service';
import type SwagPayPalPaymentService from './core/service/api/swag-paypal-payment.service';
import type SwagPayPalPosSettingApiService from './core/service/api/swag-paypal-pos-setting.api.service';
import type SwagPayPalPosWebhookRegisterService from './core/service/api/swag-paypal-pos-webhook-register.service';
import type SwagPayPalPosApiService from './core/service/api/swag-paypal-pos.api.service';
import type SwagPayPalWebhookService from './core/service/api/swag-paypal-webhook.service';
import type SwagPayPalSettingsService from './core/service/api/swag-paypal-settings.service';
import type { MerchantInformationStore } from './app/store/swag-paypal-merchant-information.store';
import type { SettingsStore } from './app/store/swag-paypal-settings.store';
import type { I18n } from 'vue-i18n';

declare global {
    type TEntity<T extends keyof EntitySchema.Entities> = Entity<T>;
    type TEntityCollection<T extends keyof EntitySchema.Entities> = EntitySchema.EntityCollection<T>;
    type TRepository<T extends keyof EntitySchema.Entities> = Repository<T>;
    type TCriteria = Criteria;
    type PropType<T> = TPropType<T>;

    interface MixinContainer {
        'swag-paypal-notification': typeof SwagPaypalNotificationMixin;
        'swag-paypal-pos-catch-error': typeof SwagPaypalPosCatchErrorMixin;
        'swag-paypal-pos-log-label': typeof SwagPaypalPosLogLabelMixin;
        'swag-paypal-settings': typeof SwagPaypalSettingsMixin;
        'swag-paypal-merchant-information': typeof SwagPaypalMerchantInformationMixin;
    }

    interface ServiceContainer {
        SwagPayPalPosSettingApiService: SwagPayPalPosSettingApiService;
        SwagPayPalPosApiService: SwagPayPalPosApiService;
        SwagPayPalPosWebhookRegisterService: SwagPayPalPosWebhookRegisterService;
        SwagPayPalWebhookService: SwagPayPalWebhookService;
        SwagPayPalPaymentService: SwagPayPalPaymentService;
        SwagPayPalOrderService: SwagPayPalOrderService;
        SwagPaypalPaymentMethodService: SwagPaypalPaymentMethodService;
        SwagPayPalDisputeApiService: SwagPayPalDisputeApiService;
        SwagPayPalSettingsService: SwagPayPalSettingsService;
    }

    interface PiniaRootState {
        swagPayPalMerchantInformation: MerchantInformationStore;
        swagPayPalSettings: SettingsStore;
    }
}

declare module '@vue/runtime-core' {
    interface ComponentCustomProperties {
        $super: (name: string) => $TSFixMe;
        // eslint-disable-next-line @typescript-eslint/no-empty-object-type
        $te: I18n<{}, {}, {}, string, true>['global']['te'];
    }
}
