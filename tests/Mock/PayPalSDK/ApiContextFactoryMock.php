<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPalSDK;

use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Context\ApiContext;
use Shopware\PayPalSDK\Context\CredentialsOAuthContext;
use Swag\PayPal\RestApi\ApiContextFactoryInterface;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\Test\Helper\ConstantsForTesting;

/**
 * @internal
 */
#[Package('checkout')]
class ApiContextFactoryMock implements ApiContextFactoryInterface
{
    public function getApiContext(?string $salesChannelId, string $partnerAttributionId = PartnerAttributionId::PAYPAL_CLASSIC): ApiContext
    {
        return new ApiContext(
            new CredentialsOAuthContext(ConstantsForTesting::VALID_CLIENT_ID, ConstantsForTesting::VALID_CLIENT_SECRET),
            true,
        );
    }
}
