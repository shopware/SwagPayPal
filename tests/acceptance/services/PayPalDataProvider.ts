const DataDefinition = {
    CLIENT_ID: (value: string | undefined) => value ?? '',
    CLIENT_SECRET: (value: string | undefined) => value ?? '',
    MERCHANT_ID: (value: string | undefined) => value ?? '',
    CUSTOMER_ID: (value: string | undefined) => value ?? '',
    CUSTOMER_PASSWORD: (value: string | undefined) => value ?? '',
    SANDBOX: (value: string | undefined) => value ?? 'true',
};

export type Data = {
    [key in keyof typeof DataDefinition]: ReturnType<(typeof DataDefinition)[key]>;
};

export class PayPalDataProvider {
    private data: Partial<Data> = {};

    constructor() {
        for (const env in process.env) {
            if (!env.startsWith('PAYPAL_')) {
                continue;
            }

            const rawKey = String(env).replace(/^PAYPAL_/, '');

            if (!(rawKey in DataDefinition)) {
                continue;
            }

            const key = rawKey as keyof typeof DataDefinition;
            const preprocessor = DataDefinition[key];

            this.data[key] = preprocessor(process.env[env]);
        }
    }

    public has<T extends keyof Data>(key: T): boolean {
        return this.data[key] !== undefined;
    }

    public get<T extends keyof Data>(key: T, defaultValue: Data[T] | undefined = undefined): Data[T] {
        if (!this.has(key) && defaultValue === undefined) {
            throw new Error(`Env PAYPAL_${key} for data value ${key} is not set.`);
        }

        return this.data[key] ?? (defaultValue!);
    }
}
