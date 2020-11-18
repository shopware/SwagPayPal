import template from './swag-image-slider.html.twig';
import './swag-image-slider.scss';

const { Component } = Shopware;

/**
 * @deprecated tag:v3.0.0 - Will be removed when sw-image-slider is in minimal required Shopware version (probably 6.3.3.0)
 */
Component.register('swag-image-slider', {
    template,

    props: {
        images: {
            type: Array,
            required: false,
            default: []
        },

        canvasWidth: {
            type: Number,
            required: true
        },

        canvasHeight: {
            type: Number,
            required: true
        },

        gap: {
            type: Number,
            required: false,
            default: 20
        },

        overflow: {
            type: String,
            required: false,
            default: 'hidden',
            validator(value) {
                return ['hidden', 'visible'].includes(value);
            }
        }
    },

    data() {
        return {
            currentPageNumber: 0
        };
    },

    computed: {
        hasImages() {
            return this.images && this.images.length > 0;
        },

        componentStyles() {
            return {
                width: `${this.canvasWidth}px`
            };
        },

        containerStyles() {
            return {
                ...this.componentStyles,
                height: `${this.canvasHeight}px`,
                overflow: this.overflow
            };
        },

        scrollableContainerStyles() {
            return {
                width: `${this.images.length * this.canvasWidth + (this.images.length - 1) * this.gap}px`,
                transform: `translateX(-${this.currentPageNumber * (this.canvasWidth + this.gap)}px)`
            };
        }
    },

    methods: {
        onButtonClick(pageNumber) {
            this.currentPageNumber = pageNumber;
        },

        elementStyles(index) {
            let marginRight = null;
            if (index !== this.images.length - 1) {
                marginRight = `${this.gap}px`;
            }

            return {
                ...this.componentStyles,
                height: `${this.canvasHeight}px`,
                marginRight
            };
        },

        imageAlt(index) {
            return this.$tc('swag-image-slider.imageAlt', 0, {
                index: index + 1,
                total: this.images.length
            });
        }
    }
});
