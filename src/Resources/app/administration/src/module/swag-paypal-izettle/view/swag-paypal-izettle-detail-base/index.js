import template from './swag-paypal-izettle-detail-base.html.twig';

const { Component, Mixin } = Shopware;

Component.register('swag-paypal-izettle-detail-base', {
    template,

    inject: [
        'salesChannelService',
        'repositoryFactory'
    ],

    mixins: [
        Mixin.getByName('placeholder')
    ],

    props: {
        salesChannel: {
            type: Object,
            required: false
        },
        isLoading: {
            type: Boolean,
            default: false
        },
        isNewEntity: {
            type: Boolean,
            default: false
        }
    },

    data() {
        return {
            showDeleteModal: false
        };
    },

    computed: {
        salesChannelRepository() {
            return this.repositoryFactory.create('sales_channel');
        }
    },

    methods: {
        onCloseDeleteModal() {
            this.showDeleteModal = false;
        },

        onConfirmDelete() {
            this.showDeleteModal = false;

            this.$nextTick(() => {
                this.deleteSalesChannel(this.salesChannel.id);
                this.$router.push({ name: 'sw.dashboard.index' });
            });
        },

        deleteSalesChannel(salesChannelId) {
            this.salesChannelRepository.delete(salesChannelId, Shopware.Context.api).then(() => {
                this.$root.$emit('sales-channel-change');
            });
        },

        forceUpdate() {
            this.$forceUpdate();
        }
    }
});
