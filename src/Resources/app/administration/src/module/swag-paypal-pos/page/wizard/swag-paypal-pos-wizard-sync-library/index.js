import template from './swag-paypal-pos-wizard-sync-library.html.twig';
import './swag-paypal-pos-wizard-sync-library.scss';

const { Component } = Shopware;

Component.register('swag-paypal-pos-wizard-sync-library', {
    template,

    inject: [
        'SwagPayPalPosSettingApiService'
    ],

    props: {
        salesChannel: {
            type: Object,
            required: true
        },
        cloneSalesChannelId: {
            type: String,
            required: false
        },
        saveSalesChannel: {
            type: Function,
            required: true
        }
    },

    data() {
        return {
            shopwareProductsCount: 0,
            iZettleProductsCount: 0
        };
    },

    computed: {
        optionReplace() {
            return {
                name: this.$tc('swag-paypal-pos.wizard.syncLibrary.optionReplaceLabel'),
                description: this.$tc('swag-paypal-pos.wizard.syncLibrary.optionReplaceDescription')
            };
        },

        optionAdd() {
            return {
                name: this.$tc('swag-paypal-pos.wizard.syncLibrary.optionAddLabel'),
                description: this.$tc('swag-paypal-pos.wizard.syncLibrary.optionAddDescription')
            };
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.updateButtons();
            this.setTitle();
            this.fetchProductCounts();
        },

        setTitle() {
            this.$emit('frw-set-title', this.$tc('swag-paypal-pos.wizard.syncLibrary.modalTitle'));
        },

        updateButtons() {
            const buttonConfig = [
                {
                    key: 'back',
                    label: this.$tc('sw-first-run-wizard.general.buttonBack'),
                    position: 'left',
                    action: this.routeBackToProductSelection,
                    disabled: false
                },
                {
                    key: 'next',
                    label: this.$tc('sw-first-run-wizard.general.buttonNext'),
                    position: 'right',
                    variant: 'primary',
                    action: this.routeToSyncPrices,
                    disabled: false
                }
            ];

            this.$emit('buttons-update', buttonConfig);
        },

        routeBackToProductSelection() {
            this.$router.push({
                name: 'swag.paypal.pos.wizard.productSelection',
                params: { id: this.salesChannel.id }
            });
        },

        routeToSyncPrices() {
            this.toggleLoadingState(true);

            this.saveSalesChannel().then(() => {
                this.toggleLoadingState(false);
                this.$router.push({
                    name: 'swag.paypal.pos.wizard.syncPrices',
                    params: { id: this.salesChannel.id }
                });
            }).finally(() => {
                this.toggleLoadingState(false);
            });
        },

        forceUpdate() {
            this.$forceUpdate();
        },

        toggleLoadingState(state) {
            this.isConnecting = state;
            this.$emit('toggle-loading', state);
        },

        fetchProductCounts() {
            this.toggleLoadingState(true);
            this.SwagPayPalPosSettingApiService.getProductCount(
                this.salesChannel.id,
                this.cloneSalesChannelId
            ).then((response) => {
                this.shopwareProductsCount = response.localCount;
                this.iZettleProductsCount = response.remoteCount;
            }).finally(() => {
                this.toggleLoadingState(false);
            });
        }
    }
});
