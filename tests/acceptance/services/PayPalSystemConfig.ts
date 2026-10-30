import { expect } from '@shopware-ag/acceptance-test-suite';

type ConfigRow = {
    salesChannelId: string | null
    configurationKey: string
    configurationValue: unknown
};

export async function readPayPalSetting(AdminApiContext: FixtureTypes['AdminApiContext'], key: string): Promise<unknown> {
    const response = await AdminApiContext.post('search/system-config', {
        data: {
            limit: 25,
            filter: [
                {
                    type: 'equals',
                    field: 'configurationKey',
                    value: key,
                },
            ],
        },
    });

    expect(response.ok(), `Should read system config ${key}`).toBeTruthy();

    const json = await response.json() as { data: ConfigRow[] };
    const globalConfig = json.data.find(row => row.salesChannelId === null);

    return globalConfig?.configurationValue;
}

export async function savePayPalSettings(
    AdminApiContext: FixtureTypes['AdminApiContext'],
    values: Record<string, unknown>,
): Promise<void> {
    const response = await AdminApiContext.post('./_action/system-config/batch', {
        data: {
            null: values,
        },
    });

    expect(response.ok(), 'Should save the PayPal settings that this test changed').toBeTruthy();
}
