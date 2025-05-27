const DataDefinition = {
    CLIENT_ID: String,
    CLIENT_SECRET: String,
    SANDBOX: Boolean,
};

export type Data = Record<string, string> & {
    [key in keyof typeof DataDefinition]: ReturnType<(typeof DataDefinition)[key]>;
};

export class PayPalDataProvider {
    private data: Partial<Data> = {};

    constructor() {
        for (const env in process.env) {
            if (!env.startsWith('PAYPAL_')) {
                continue;
            }

            const key = String(env).replace(/^PAYPAL_/, '') as keyof typeof DataDefinition;
            const preprocessor = DataDefinition[key] ?? String;

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
