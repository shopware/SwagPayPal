import { 
    FixtureTypes,
    TestDataService,
    CreatedRecord,
    SyncApiOperation,
    DataServiceOptions,
    APIResponse,
} from '@shopware-ag/acceptance-test-suite';
import { expect } from '@playwright/test';
import { SystemConfig } from 'SwagPayPal/types';

const defaultPayPalConfig: SystemConfig = {
    'SwagPayPal.settings.intent': 'CAPTURE',
    'SwagPayPal.settings.submitCart': true,
    'SwagPayPal.settings.landingPage': 'NO_PREFERENCE',
    'SwagPayPal.settings.sendOrderNumber': true,
    'SwagPayPal.settings.ecsDetailEnabled': true,
    'SwagPayPal.settings.ecsCartEnabled': true,
    'SwagPayPal.settings.ecsOffCanvasEnabled': true,
    'SwagPayPal.settings.ecsLoginEnabled': true,
    'SwagPayPal.settings.ecsListingEnabled': false,
    'SwagPayPal.settings.ecsButtonColor': 'gold',
    'SwagPayPal.settings.ecsButtonShape': 'sharp',
    'SwagPayPal.settings.ecsShowPayLater': true,
    'SwagPayPal.settings.spbCheckoutEnabled': true,
    'SwagPayPal.settings.spbAlternativePaymentMethodsEnabled': false,
    'SwagPayPal.settings.spbButtonColor': 'gold',
    'SwagPayPal.settings.spbButtonShape': 'sharp',
    'SwagPayPal.settings.spbShowPayLater': true,
    'SwagPayPal.settings.installmentBannerDetailPageEnabled': true,
    'SwagPayPal.settings.installmentBannerCartEnabled': true,
    'SwagPayPal.settings.installmentBannerOffCanvasCartEnabled': true,
    'SwagPayPal.settings.installmentBannerLoginPageEnabled': true,
    'SwagPayPal.settings.installmentBannerFooterEnabled': true,
    'SwagPayPal.settings.puiCustomerServiceInstructions': 'Details zum Kundenservice finden Sie auf unserer Webseite',
    'SwagPayPal.settings.acdcForce3DS': false,
    'SwagPayPal.settings.excludedProductIds': [],
    'SwagPayPal.settings.excludedProductStreamIds': [],
    'SwagPayPal.settings.vaultingEnabledACDC': false,
    'SwagPayPal.settings.vaultingEnabledWallet': false,
    'SwagPayPal.settings.vaultingEnabledVenmo': false,
    'SwagPayPal.settings.crossBorderMessagingEnabled': false,
    'SwagPayPal.settings.crossBorderBuyerCountry': null,
};  

export class PayPalTestDataService extends TestDataService {
    public readonly namePrefix: string = 'Test-';
        public readonly nameSuffix: string = '';
        public readonly defaultCountryId: string;
      
        /**
         * Configuration of higher priority entities for the cleanup operation in PayPal.
         * These entities will be deleted before others.
         * This will prevent restricted delete operations of associated entities.
         *
         * @private
         */
        private payPalHighPriorityEntities: any[]  = [];

    /**
     * A registry of all created records in PayPal.
     *
     * @private
     */
    private createdPayPalRecords: CreatedRecord[] = [];

    private restorePayPalConfig = false;

    /**
     * Set the configuration of automated data clean up.
     * If set to "true" the data service will delete all entities created by it.
     *
     * @param shouldCleanUp - The config setting for the automated data clean up. Default is "true".
     */
    setCleanUp(shouldCleanUp = true) {
    super.setCleanUp(shouldCleanUp);
    }

    constructor(
        AdminApiClient: FixtureTypes['AdminApiContext'],
        IdProvider: FixtureTypes['IdProvider'],
        options: DataServiceOptions
    ) {
        super(AdminApiClient, IdProvider, options);

        if (options.namePrefix) {
            this.namePrefix = options.namePrefix;
        }

        if (options.nameSuffix) {
            this.nameSuffix = options.nameSuffix;
        }
        this.defaultCountryId = options.defaultCountryId;
    }

    /**
     * Set PayPal settings config for default sales channel
     *
     * @param configs - Key value pairs to set
     */
    async setPayPalSettings(salesChannelId: string | 'null',  overrides: Partial<SystemConfig> = {}): Promise<APIResponse> {
        const mergedConfig: SystemConfig = {
            ...defaultPayPalConfig,
            ...overrides,
        };
        const configResponse = await this.AdminApiClient.post('./_action/paypal/save-settings', {
            data: {
                [salesChannelId]: mergedConfig,
            },
        });
        expect(configResponse.ok()).toBeTruthy();
        await this.clearCaches();
        if (Object.keys(overrides).length > 0) {
            this.restorePayPalConfig = true;
        }
        return configResponse;
    }
    

    /**
     * Adds an entity reference to the registry of created records in PayPal.
     * All entities added to the registry will be deleted by the cleanup call.
     *
     * @param resource - The resource name of the entity.
     * @param payload - You can pass a payload object for the delete operation or simply pass the uuid of the entity.
     */
    addPayPalCreatedRecord(resource: string, payload: string | Record<string, string>) {
        const res = resource.replace('-', '_');

        if (typeof payload === 'string') {
            this.createdPayPalRecords.push({
                resource: res,
                payload: { id: payload },
            });
        } else {
            this.createdPayPalRecords.push({ resource: res, payload });
        }
    }

    /**
     * Will delete all entities created by the data service via sync API.
     */
    async cleanUpPayPalEntities() {
        if (!this.shouldCleanUp) {
            return null;
        }

        const deleteOperations: Record<string, SyncApiOperation> = {};
        const priorityDeleteOperations: Record<string, SyncApiOperation> = {};

        this.createdPayPalRecords.forEach((record) => {

            if (this.payPalHighPriorityEntities.includes(record.resource)) {
                if (!priorityDeleteOperations[`delete-${record.resource}`]) {
                    priorityDeleteOperations[`delete-${record.resource}`] = {
                        entity: record.resource,
                        action: 'delete',
                        payload: [],
                    };
                }
                priorityDeleteOperations[`delete-${record.resource}`].payload.push(record.payload);
            } else {
                if (!deleteOperations[`delete-${record.resource}`]) {
                    deleteOperations[`delete-${record.resource}`] = {
                        entity: record.resource,
                        action: 'delete',
                        payload: [],
                    };
                }

                deleteOperations[`delete-${record.resource}`].payload.push(record.payload);
            }
        });

        const priorityDeleteOperationsResponse = await this.AdminApiClient.post('_action/sync', {
            data: priorityDeleteOperations,
        });

        expect(priorityDeleteOperationsResponse.ok()).toBeTruthy();

        // Restore PayPal config defaults
        if (this.restorePayPalConfig) {
            await this.setPayPalSettings('null', {});
        }

        const deleteOperationsResponse = await this.AdminApiClient.post('_action/sync', {
            data: deleteOperations,
        });

        expect(deleteOperationsResponse.ok()).toBeTruthy();

        return deleteOperationsResponse;
    }
}

