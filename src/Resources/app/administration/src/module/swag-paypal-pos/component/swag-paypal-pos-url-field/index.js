import template from './swag-paypal-pos-url-field.html.twig';
import punycode from 'punycode';

Shopware.Component.register('swag-paypal-pos-url-field', {
    template,

    emits: ['update:model-value'],

    props: {
        modelValue: {
            type: String,
            required: false,
            default: null,
        },
    },

    data() {
        return {
            value: '',
        };
    },

    computed: {
        // parse value to an url. Will be emitted as modelValue via a watcher
        url() {
            if (!this.value) {
                return '';
            }

            try {
                const url = new URL(this.value.startsWith('http') ? this.value : `https://${this.value}`);
                url.protocol = 'https:';

                return url.toString().replace(url.hash || url.search ? '' : /\/$/, '');
            } catch (e) {
                return null;
            }
        },
    },

    watch: {
        // any updates to modelValue should be parsed and visible, therefore update value
        modelValue: {
            handler(value) {
                this.updateValue(value ?? '');
            },
            immediate: true,
        },

        // emit the url to update modelValue
        url(value) {
            // only update if the value is valid
            if (value !== null) {
                this.$emit('update:model-value', value);
            }
        },
    },

    methods: {
        // Since  incoming values could be in a URL representation, we need to transform it into something usable.
        // The transformations applied will only affect non-ascii characters.
        updateValue(value) {
            this.value = decodeURI(punycode.toUnicode(value.replace(/^https?:\/\//, '')));
        },
    },
});
