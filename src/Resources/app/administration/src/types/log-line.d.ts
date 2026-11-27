declare namespace EntitySchema {
    interface Entities {
        swag_paypal_log: swag_paypal_log;
    }

    type SwagPayPalLogId = string & { readonly __brand: 'SwagPayPalLogId' };

    interface EntityKeys {
        swag_paypal_log: SwagPayPalLogId;
    }

    interface swag_paypal_log {
        id: EntityKey<'swag_paypal_log'>;
        level: number;
        code?: string | null;
        salesChannelId?: string | null;
        uid?: string | null;
        message: string;
        context?: { [key: string]: unknown } | null;
        extra?: { [key: string]: unknown } | null;
        createdAt: string;
        updatedAt?: string | null;
    }
}
