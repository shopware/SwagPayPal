import type { APIRequestContext } from '@shopware-ag/acceptance-test-suite';
import { request as apiRequest } from '@playwright/test';

export async function openViewerClient(
    TestDataService: FixtureTypes['TestDataService'],
    request: APIRequestContext,
): Promise<APIRequestContext> {
    const role = await TestDataService.createAclRole({
        privileges: ['swag_paypal.viewer'],
    });
    const user = await TestDataService.createUser({
        admin: false,
        password: 'shopware',
        aclRoles: [{ id: role.id }],
    } as Parameters<FixtureTypes['TestDataService']['createUser']>[0]);

    const tokenResponse = await request.post('api/oauth/token', {
        data: {
            client_id: 'administration',
            grant_type: 'password',
            scope: 'write',
            username: user.username,
            password: 'shopware',
        },
    });

    if (!tokenResponse.ok()) {
        throw new Error(`Viewer login failed with HTTP ${tokenResponse.status()}. APP_URL must point at the shop.`);
    }

    const tokenJson = await tokenResponse.json() as { access_token?: string };
    if (!tokenJson.access_token) {
        throw new Error('Viewer login did not return an access token. APP_URL must point at the shop.');
    }

    // Login is `{APP_URL}api/oauth/token`. Two segments up keeps a path prefix such as `/shop/`.
    return apiRequest.newContext({
        baseURL: new URL('../../', tokenResponse.url()).href,
        extraHTTPHeaders: {
            Authorization: `Bearer ${tokenJson.access_token}`,
        },
    });
}
