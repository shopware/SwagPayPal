import template from './swag-paypal-izettle-wizard.html.twig';
import './swag-paypal-izettle-wizard.scss';

const { Component } = Shopware;

Component.extend('swag-paypal-izettle-wizard', 'sw-first-run-wizard-modal', {
    template,

    metaInfo() {
        return {
            title: this.title
        };
    },

    props: {
        isLoading: {
            type: Boolean,
            required: true
        },
        salesChannel: {
            type: Object,
            required: true
        },
        storefrontSalesChannelId: {
            type: String,
            required: false
        },
        isTestingCredentials: {
            type: Boolean,
            required: false
        },
        isTestCredentialsSuccessful: {
            type: Boolean,
            required: false
        }
    },

    data() {
        return {
            stepper: {
                connection: {
                    name: 'swag.paypal.izettle.wizard.connection',
                    variant: 'large',
                    navigationIndex: 1
                },
                'sales-channel': {
                    name: 'swag.paypal.izettle.wizard.sales-channel',
                    variant: 'large',
                    navigationIndex: 2
                },
                locale: {
                    name: 'swag.paypal.izettle.wizard.locale',
                    variant: 'large',
                    navigationIndex: 3
                },
                'product-stream': {
                    name: 'swag.paypal.izettle.wizard.product-stream',
                    variant: 'large',
                    navigationIndex: 4
                },
                sync: {
                    name: 'swag.paypal.izettle.wizard.sync',
                    variant: 'large',
                    navigationIndex: 5
                },
                finish: {
                    name: 'swag.paypal.izettle.wizard.finish',
                    variant: 'large',
                    navigationIndex: 6
                }
            }
        };
    },

    computed: {
        stepInitialItemVariants() {
            const maxNavigationIndex = 6;
            const { navigationIndex } = this.currentStep;
            const navigationSteps = [];

            for (let i = 1; i < maxNavigationIndex; i += 1) {
                if (i < navigationIndex) {
                    navigationSteps.push('success');
                } else if (i === navigationIndex) {
                    navigationSteps.push('info');
                } else {
                    navigationSteps.push('disabled');
                }
            }
            return navigationSteps;
        }
    },

    watch: {
        '$route'(to) {
            const toName = to.name.replace('swag.paypal.izettle.wizard.', '');

            this.currentStep = this.stepper[toName];
        }
    },

    mounted() {
        if (!this.salesChannel.extensions.paypalIZettleSalesChannel.username) {
            this.$router.push({ name: 'swag.paypal.izettle.wizard' });
        }

        const step = this.$route.name.replace('swag.paypal.izettle.wizard.', '');

        this.currentStep = this.stepper[step];
    },

    methods: {
        finishWizard() {
            this.$emit('wizard-finish');
        },

        updateStorefrontSalesChannel(storefrontSalesChannelId) {
            this.$emit('update-storefront-sales-channel', storefrontSalesChannelId);
        },

        cancelWizard() {
            this.$emit('wizard-cancel');
        },

        testCredentials() {
            this.$emit('test-credentials');
        }
    }
});
