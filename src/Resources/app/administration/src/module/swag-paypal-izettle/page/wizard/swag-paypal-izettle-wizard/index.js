import template from './swag-paypal-izettle-wizard.html.twig';
import './swag-paypal-izettle-wizard.scss';
import { IZETTLE_SALES_CHANNEL_TYPE_ID } from '../../../swag-paypal-izettle-consts';

const { Component, Context } = Shopware;
const { Criteria } = Shopware.Data;

Component.extend('swag-paypal-izettle-wizard', 'sw-first-run-wizard-modal', {
    template,

    inject: [
        'SwagPayPalIZettleApiService',
        'SwagPayPalIZettleSettingApiService',
        'salesChannelService',
        'repositoryFactory'
    ],

    mixins: [
        'notification'
    ],

    data() {
        return {
            showModal: true,
            isLoading: false,
            salesChannel: {},
            cloneSalesChannelId: null,
            stepperPages: [
                'connection',
                'connectionSuccess',
                'connectionDisconnect',
                'customization',
                'productSelection',
                'syncLibrary',
                'syncPrices',
                'finish'
            ],
            stepper: {},
            currentStep: {}
        };
    },

    metaInfo() {
        return {
            title: this.wizardTitle
        };
    },

    computed: {
        displayStepperPages() {
            return this.stepperPages.filter((item) => {
                return item !== 'connectionDisconnect';
            });
        },

        stepInitialItemVariants() {
            const maxNavigationIndex = this.stepperPages.length;
            const { navigationIndex } = this.currentStep;
            const navigationSteps = [];

            for (let i = 1; i <= maxNavigationIndex; i += 1) {
                if (i < navigationIndex) {
                    navigationSteps.push('success');
                } else if (i === navigationIndex) {
                    navigationSteps.push('info');
                } else {
                    navigationSteps.push('disabled');
                }
            }
            return navigationSteps;
        },

        paypalIZettleSalesChannelRepository() {
            return this.repositoryFactory.create('swag_paypal_izettle_sales_channel');
        },

        salesChannelRepository() {
            return this.repositoryFactory.create('sales_channel');
        },

        salesChannelCriteria() {
            return (new Criteria())
                .addAssociation('countries')
                .addAssociation('currencies')
                .addAssociation('domains')
                .addAssociation('languages');
        },

        wizardTitle() {
            const params = [
                this.$tc('global.sw-admin-menu.textShopwareAdmin'),
                this.$tc('swag-paypal-izettle.general.moduleTitle'),
                this.title
            ];

            return params.reverse().join(' | ');
        }
    },

    watch: {
        '$route'(to) {
            const toName = to.name.replace('swag.paypal.izettle.wizard.', '');

            this.currentStep = this.stepper[toName];
        }
    },

    mounted() {
        this.mountedComponent();
    },

    methods: {
        createdComponent() {
            this.$super('createdComponent');
            this.generateStepper();

            const salesChannelId = this.$route.params.id;
            if (salesChannelId) {
                this.loadSalesChannel();
                return;
            }

            this.createNewSalesChannel();
        },

        mountedComponent() {
            const step = this.$route.name.replace('swag.paypal.izettle.wizard.', '');
            this.currentStep = this.stepper[step];
        },

        generateStepper() {
            let index = 1;
            this.stepper = this.stepperPages.reduce((accumulator, pageName) => {
                if (pageName === 'connectionDisconnect') {
                    index -= 1;
                }

                accumulator[pageName] = {
                    name: `swag.paypal.izettle.wizard.${pageName}`,
                    variant: 'large',
                    navigationIndex: index
                };

                if (index === 1) {
                    this.currentStep = accumulator[pageName];
                }
                index += 1;

                return accumulator;
            }, {});
        },

        onCloseModal() {
            this.showModal = false;
            this.$nextTick(() => {
                if (!this.salesChannel._isNew && (this.$route.params.id || this.salesChannel.id)) {
                    this.$router.push({ name: 'swag.paypal.izettle.detail.overview', params: { id: this.salesChannel.id } });

                    return;
                }

                this.$router.push({ name: 'sw.dashboard.index' });
            });
        },

        finishWizard() {
            this.save().then(() => {
                this.onCloseModal();
            });
        },

        save(activateSalesChannel = false) {
            if (activateSalesChannel) {
                this.salesChannel.active = true;
            }

            return this.salesChannelRepository.save(this.salesChannel, Context.api).then(async () => {
                this.isLoading = false;
                this.isSaveSuccessful = true;
                this.isNewEntity = false;

                this.$root.$emit('sales-channel-change');
                await this.loadSalesChannel();

                this.cloneProductVisibility();
                this.registerWebhook();
            }).catch(() => {
                this.isLoading = false;

                this.createNotificationError({
                    message: this.$tc('sw-sales-channel.detail.messageSaveError', 0, {
                        name: this.salesChannel.name || this.placeholder(this.salesChannel, 'name')
                    })
                });
            });
        },

        registerWebhook() {
            return this.SwagPayPalIZettleWebhookRegisterService.registerWebhook(this.salesChannel.id)
                .catch(() => {
                    this.createNotificationError({
                        title: this.$tc('global.default.error'),
                        message: this.$tc('swag-paypal-izettle.messageWebhookRegisterError')
                    });
                });
        },

        cloneProductVisibility() {
            if (this.cloneSalesChannelId === null) {
                return;
            }

            this.SwagPayPalIZettleSettingApiService.cloneProductVisibility(
                this.cloneSalesChannelId,
                this.salesChannel.id
            ).catch((errorResponse) => {
                if (errorResponse.response.data && errorResponse.response.data.errors) {
                    this.createNotificationError({
                        message: this.$tc('swag-paypal-izettle.messageCloneError')
                    });
                }
            });
        },

        createNewSalesChannel() {
            if (Context.api.languageId !== Context.api.systemLanguageId) {
                Context.api.languageId = Context.api.systemLanguageId;
            }

            this.previousApiKey = null;
            this.salesChannel = this.salesChannelRepository.create(Context.api);
            this.salesChannel.typeId = IZETTLE_SALES_CHANNEL_TYPE_ID;
            this.salesChannel.name = this.$tc('swag-paypal-izettle.wizard.salesChannelPrototypeName');
            this.salesChannel.active = false;

            this.salesChannel.extensions.paypalIZettleSalesChannel
                = this.paypalIZettleSalesChannelRepository.create(Context.api);

            Object.assign(
                this.salesChannel.extensions.paypalIZettleSalesChannel,
                {
                    mediaDomain: '',
                    apiKey: '',
                    imageDomain: '',
                    productStreamId: null,
                    syncPrices: true,
                    replace: false
                }
            );

            this.salesChannelService.generateKey().then((response) => {
                this.salesChannel.accessKey = response.accessKey;
            }).catch(() => {
                this.createNotificationError({
                    message: this.$tc('sw-sales-channel.detail.messageAPIError')
                });
            });
        },

        loadSalesChannel() {
            const salesChannelId = this.$route.params.id || this.salesChannel.id;
            if (!salesChannelId) {
                return new Promise();
            }

            this.isLoading = true;
            return this.salesChannelRepository.get(salesChannelId, Shopware.Context.api, this.salesChannelCriteria)
                .then((entity) => {
                    this.salesChannel = entity;
                    this.previousApiKey = entity.extensions.paypalIZettleSalesChannel.apiKey;
                    this.isLoading = false;
                });
        },

        updateCloneSalesChannel(cloneSalesChannelId) {
            this.cloneSalesChannelId = cloneSalesChannelId;
        },

        toggleLoading(state) {
            this.isLoading = state;
        }
    }
});
