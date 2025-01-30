import type * as PayPal from 'SwagPayPal/types';

const Default = {
    merchantIntegrations: {
        merchant_id: '2CWUTJMHUSECB',
        tracking_id: 'test@example.com',
        products: [
            { name: 'BASIC_PPPLUS_CORE' },
            { name: 'BASIC_PPPLUS_PUI' },
            { name: 'BASIC_PPPLUS_GUEST_CC' },
            { name: 'BASIC_PPPLUS_GUEST_ELV' },
            {
                name: 'PPCP_STANDARD',
                vetting_status: 'SUBSCRIBED',
                capabilities: [
                    'ACCEPT_DONATIONS',
                    'BANK_MANAGED_WITHDRAWAL',
                    'GUEST_CHECKOUT',
                    'INSTALLMENTS',
                    'PAY_WITH_PAYPAL',
                    'PAYPAL_CHECKOUT_ALTERNATIVE_PAYMENT_METHODS',
                    'PAYPAL_CHECKOUT_PAY_WITH_PAYPAL_CREDIT',
                    'PAYPAL_CHECKOUT',
                    'QR_CODE',
                    'SEND_INVOICE',
                    'SUBSCRIPTIONS',
                    'WITHDRAW_FUNDS_TO_DOMESTIC_BANK',
                ],
            },
            {
                name: 'PAYMENT_METHODS',
                vetting_status: 'SUBSCRIBED',
                capabilities: [
                    'APPLE_PAY',
                    'GOOGLE_PAY',
                    'IDEAL',
                    'PAY_UPON_INVOICE',
                    'PAY_WITH_PAYPAL',
                    'SEPA',
                    'VAT_TAX',
                ],
            },
            {
                name: 'ADVANCED_VAULTING',
                vetting_status: 'SUBSCRIBED',
                capabilities: ['PAYPAL_WALLET_VAULTING_ADVANCED'],
            },
            {
                name: 'PPCP_CUSTOM',
                vetting_status: 'SUBSCRIBED',
                capabilities: [
                    'AMEX_OPTBLUE',
                    'APPLE_PAY',
                    'CARD_PROCESSING_VIRTUAL_TERMINAL',
                    'COMMERCIAL_ENTITY',
                    'CUSTOM_CARD_PROCESSING',
                    'DEBIT_CARD_SWITCH',
                    'FRAUD_TOOL_ACCESS',
                    'GOOGLE_PAY',
                    'PAY_UPON_INVOICE',
                    'PAYPAL_WALLET_VAULTING_ADVANCED',
                ],
            },
        ],
        capabilities: [
            { status: 'ACTIVE', name: 'ACCEPT_DONATIONS' },
            { status: 'ACTIVE', name: 'AMEX_OPTBLUE' },
            { status: 'ACTIVE', name: 'APPLE_PAY' },
            { status: 'ACTIVE', name: 'BANK_MANAGED_WITHDRAWAL' },
            { status: 'ACTIVE', name: 'CARD_PROCESSING_VIRTUAL_TERMINAL' },
            { status: 'ACTIVE', name: 'COMMERCIAL_ENTITY' },
            { status: 'ACTIVE', name: 'CUSTOM_CARD_PROCESSING' },
            { status: 'ACTIVE', name: 'DEBIT_CARD_SWITCH' },
            { status: 'ACTIVE', name: 'FRAUD_TOOL_ACCESS' },
            { status: 'ACTIVE', name: 'GOOGLE_PAY' },
            { status: 'ACTIVE', name: 'GUEST_CHECKOUT' },
            { status: 'ACTIVE', name: 'IDEAL' },
            { status: 'ACTIVE', name: 'INSTALLMENTS' },
            { status: 'ACTIVE', name: 'PAY_UPON_INVOICE' },
            { status: 'ACTIVE', name: 'PAY_WITH_PAYPAL' },
            { status: 'ACTIVE', name: 'PAYPAL_CHECKOUT_ALTERNATIVE_PAYMENT_METHODS' },
            { status: 'ACTIVE', name: 'PAYPAL_CHECKOUT_PAY_WITH_PAYPAL_CREDIT' },
            { status: 'ACTIVE', name: 'PAYPAL_CHECKOUT' },
            { status: 'ACTIVE', name: 'PAYPAL_WALLET_VAULTING_ADVANCED' },
            { status: 'ACTIVE', name: 'QR_CODE' },
            { status: 'ACTIVE', name: 'SEND_INVOICE' },
            { status: 'ACTIVE', name: 'SEPA' },
            { status: 'ACTIVE', name: 'SUBSCRIPTIONS' },
            { status: 'ACTIVE', name: 'VAT_TAX' },
            { status: 'ACTIVE', name: 'WITHDRAW_FUNDS_TO_DOMESTIC_BANK' },
        ],
        oauth_integrations: [
            {
                integration_method: 'PAYPAL',
                integration_type: 'OAUTH_THIRD_PARTY',
                oauth_third_party: [{
                    merchant_client_id: 'xxFgvoYN7vzQ5KsoB4J7T1-8ylwpdVxlXCT0v0bMOILlfa4zvV8CQk4GdRXRfkPx3n4Jer_RjOH7OBzJ',
                    partner_client_id: '2H9kWfD51juip11YX0IN7SCYMq_BxXpHUnZV9hS6jx0EBaAxvDWEzJGR6XhDAfoRgiMAQGalsW9UNmmB',
                    scopes: [
                        'https://uri.paypal.com/services/payments/delay-funds-disbursement',
                        'https://uri.paypal.com/services/payments/realtimepayment',
                        'https://uri.paypal.com/services/reporting/search/read',
                        'https://uri.paypal.com/services/payments/refund',
                        'https://uri.paypal.com/services/customer/merchant-integrations/read',
                        'https://uri.paypal.com/services/disputes/update-seller',
                        'https://uri.paypal.com/services/payments/payment/authcapture',
                        'https://uri.paypal.com/services/billing-agreements',
                        'https://uri.paypal.com/services/vault/payment-tokens/read',
                        'https://uri.paypal.com/services/vault/payment-tokens/readwrite',
                        'https://uri.paypal.com/services/disputes/read-seller',
                        'https://uri.paypal.com/services/shipping/trackers/readwrite',
                    ],
                }],
            },
        ],
        granted_permissions: [],
        payments_receivable: true,
        legal_name: "Example's Test Store",
        primary_email: 'test@example.com',
        primary_email_confirmed: true,
    },
    capabilities: {
        'some-payment-method-id': 'active',
    },
} satisfies PayPal.Setting<'merchant_information'>;

const NotLoggedIn = {
    merchantIntegrations: null,
    capabilities: {
        'some-payment-method-id': 'inactive',
    },
} satisfies PayPal.Setting<'merchant_information'>;

const NonPPCP = {
    ...Default,
    merchantIntegrations: {
        ...Default.merchantIntegrations,
        products: Default.merchantIntegrations.products.filter(({ name }) => !name.includes('PPCP')),
        capabilities: Default.merchantIntegrations.capabilities.filter(({ name }) => !name.includes('PAYPAL_CHECKOUT')),
    },
} satisfies PayPal.Setting<'merchant_information'>;

const NonVault = {
    ...Default,
    merchantIntegrations: {
        ...Default.merchantIntegrations,
        products: Default.merchantIntegrations.products.filter(({ name }) => !name.includes('VAULTING')), // ! PPCP_CUSTOM.capabilities still includes VAULTING !
        capabilities: Default.merchantIntegrations.capabilities.filter(({ name }) => !name.includes('VAULTING')),
    },
} satisfies PayPal.Setting<'merchant_information'>;

export default {
    Default,
    NotLoggedIn,
    NonPPCP,
    NonVault,
};
