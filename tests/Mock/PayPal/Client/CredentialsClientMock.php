<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client;

use Swag\PayPal\PayPal\Client\CredentialsClient;
use Swag\PayPal\Test\Helper\ConstantsForTesting;

class CredentialsClientMock extends CredentialsClient
{
    public function getAccessToken(string $authCode, string $sharedId, string $nonce): string
    {
        return '';
    }

    public function getCredentials(string $accessToken, string $partnerId): array
    {
        return [
            'client_id' => ConstantsForTesting::VALID_CLIENT_ID,
            'client_secret' => ConstantsForTesting::VALID_CLIENT_SECRET,
        ];
    }
}
