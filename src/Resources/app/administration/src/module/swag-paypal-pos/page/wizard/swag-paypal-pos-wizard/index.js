import template from './swag-paypal-pos-wizard.html.twig';
import './swag-paypal-pos-wizard.scss';
import {
    PAYPAL_POS_SALES_CHANNEL_EXTENSION,
    PAYPAL_POS_SALES_CHANNEL_TYPE_ID,
} from '../../../../../constant/swag-paypal.constant';

const { Component, Context } = Shopware;
const { Criteria } = Shopware.Data;

Component.extend('swag-paypal-pos-wizard', 'sw-first-run-wizard-modal', {
    template,

    inject: [
        'SwagPayPalPosApiService',
        'SwagPayPalPosSettingApiService',
        'SwagPayPalPosWebhookRegisterService',
        'salesChannelService',
        'repositoryFactory',
    ],

    mixins: [
        Shopware.Mixin.getByName('swag-paypal-pos-catch-error'),
        Shopware.Mixin.getByName('notification'),
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
                'finish',
            ],
            stepper: {},
            currentStep: {},
        };
    },

    metaInfo() {
        return {
            title: this.wizardTitle,
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

        paypalPosSalesChannelRepository() {
            return this.repositoryFactory.create('swag_paypal_pos_sales_channel');
        },

        salesChannelRepository() {
            return this.repositoryFactory.create('sales_channel');
        },

        salesChannelCriteria() {
            return (new Criteria(1, 500))
                .addAssociation(PAYPAL_POS_SALES_CHANNEL_EXTENSION)
                .addAssociation('countries')
                .addAssociation('currencies')
                .addAssociation('domains')
                .addAssociation('languages');
        },

        wizardTitle() {
            const params = [
                this.$tc('global.sw-admin-menu.textShopwareAdmin'),
                this.$tc('swag-paypal-pos.general.moduleTitle'),
                this.title,
            ];

            return params.reverse().join(' | ');
        },
    },

    watch: {
        '$route'(to) {
            this.handleRouteUpdate(to);
        },
    },

    mounted() {
        this.mountedComponent();
    },

    methods: {
        handleRouteUpdate(to) {
            const toName = to.name.replace('swag.paypal.pos.wizard.', '');

            this.currentStep = this.stepper[toName];
        },

        createdComponent() {
            this.generateStepper();

            const salesChannelId = this.$route.params.id;
            if (salesChannelId) {
                this.loadSalesChannel();
                return;
            }

            this.createNewSalesChannel();
        },

        mountedComponent() {
            const step = this.$route.name.replace('swag.paypal.pos.wizard.', '');
            this.currentStep = this.stepper[step];
        },

        generateStepper() {
            let index = 1;
            this.stepper = this.stepperPages.reduce((accumulator, pageName) => {
                if (pageName === 'connectionDisconnect') {
                    index -= 1;
                }

                accumulator[pageName] = {
                    name: `swag.paypal.pos.wizard.${pageName}`,
                    variant: 'large',
                    navigationIndex: index,
                };

                if (index === 1) {
                    this.currentStep = accumulator[pageName];
                }
                index += 1;

                return accumulator;
            }, {});
        },

        onCloseModal() {
            if (!this.salesChannel._isNew && (this.$route.params.id || this.salesChannel.id)) {
                this.routeToDetailOverview();

                return;
            }

            this.routeToDashboard();
        },

        onFinishWizard() {
            this.routeToDetailOverview(true);
        },

        routeToDashboard() {
            this.showModal = false;

            this.$nextTick(() => {
                this.$router.push({ name: 'sw.dashboard.index' });
            });
        },

        routeToDetailOverview(finished = false) {
            this.showModal = false;

            this.save(finished).then(() => {
                if (finished) {
                    this.SwagPayPalPosApiService.startCompleteSync(this.salesChannel.id);
                }

                this.$router.push({
                    name: 'swag.paypal.pos.detail.overview',
                    params: { id: this.salesChannel.id },
                });
            });
        },

        save(activateSalesChannel = false, silentWebhook = false) {
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
                this.registerWebhook(silentWebhook);
            }).catch(() => {
                this.isLoading = false;

                this.createNotificationError({
                    message: this.$tc('sw-sales-channel.detail.messageSaveError', 0, {
                        name: this.salesChannel.name || this.placeholder(this.salesChannel, 'name'),
                    }),
                });
            });
        },

        registerWebhook(silent = false) {
            const webhookPromise = this.SwagPayPalPosWebhookRegisterService.registerWebhook(this.salesChannel.id);

            if (!silent) {
                return webhookPromise.catch(this.catchError.bind(this, 'swag-paypal-pos.messageWebhookRegisterError'));
            }

            return webhookPromise;
        },

        cloneProductVisibility() {
            if (this.cloneSalesChannelId === null) {
                return;
            }

            this.SwagPayPalPosSettingApiService.cloneProductVisibility(
                this.cloneSalesChannelId,
                this.salesChannel.id,
            ).catch((errorResponse) => {
                if (errorResponse.response.data && errorResponse.response.data.errors) {
                    this.createNotificationError({
                        message: this.$tc('swag-paypal-pos.messageCloneError'),
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
            this.salesChannel.typeId = PAYPAL_POS_SALES_CHANNEL_TYPE_ID;
            this.salesChannel.name = this.$tc('swag-paypal-pos.wizard.salesChannelPrototypeName');
            this.salesChannel.active = false;

            this.salesChannel.extensions.paypalPosSalesChannel
                = this.paypalPosSalesChannelRepository.create(Context.api);

            Object.assign(
                this.salesChannel.extensions.paypalPosSalesChannel,
                {
                    mediaDomain: '',
                    apiKey: '',
                    imageDomain: '',
                    productStreamId: null,
                    syncPrices: true,
                    replace: 0,
                },
            );

            this.salesChannelService.generateKey().then((response) => {
                this.salesChannel.accessKey = response.accessKey;
            }).catch(() => {
                this.createNotificationError({
                    message: this.$tc('sw-sales-channel.detail.messageAPIError'),
                });
            });
        },

        loadSalesChannel() {
            const salesChannelId = this.$route.params.id || this.salesChannel.id;
            if (!salesChannelId) {
                return new Promise((resolve) => { resolve(); });
            }

            this.isLoading = true;
            return this.salesChannelRepository.get(salesChannelId, Shopware.Context.api, this.salesChannelCriteria)
                .then((entity) => {
                    this.salesChannel = entity;
                    this.previousApiKey = entity.extensions.paypalPosSalesChannel.apiKey;
                    this.isLoading = false;
                });
        },

        updateCloneSalesChannel(cloneSalesChannelId) {
            this.cloneSalesChannelId = cloneSalesChannelId;
        },

        toggleLoading(state) {
            this.isLoading = state;
        },
    },
});
