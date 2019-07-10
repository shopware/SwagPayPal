<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client;

use Swag\PayPal\PayPal\Client\OnboardingClient;
use Swag\PayPal\Test\Helper\ConstantsForTesting;

class OnboardingClientMock extends OnboardingClient
{
    public function getClientCredentials(string $authCode, string $sharedId, string $nonce, string $url, string $partnerId): array
    {
        return [
            'client_id' => ConstantsForTesting::VALID_CLIENT_ID,
            'client_secret' => ConstantsForTesting::VALID_CLIENT_SECRET,
        ];
    }
}
