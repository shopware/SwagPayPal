type EntityExtensions = {
    sales_channel: {
        extensions?: {
            [key: string]: unknown;
            paypalPosSalesChannel?: TEntity<'swag_paypal_pos_sales_channel'>;
        };
    };

    shipping_method: {
        customFields?: {
            [key: string]: unknown;
            swag_paypal_carrier?: string;
            swag_paypal_carrier_other_name?: string;
        };
    };

    order_transaction: {
        customFields: {
            [key: string]: unknown;
            swag_paypal_order_id?: string;
            swag_paypal_resource_id?: string;
            swag_paypal_transaction_id?: string;
        };
    };
};

type ApplyExtension<T> = T extends keyof EntityExtensions ? EntityExtensions[T] : unknown;

export type Entity<T extends keyof EntitySchema.Entities> = EntitySchema.Entity<T> & ApplyExtension<T>;
