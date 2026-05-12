<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi;

use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Context\ApiContext;
use Shopware\PayPalSDK\Context\CredentialsOAuthContext;
use Shopware\PayPalSDK\Contract\Context\OAuthContextInterface;

/**
 * @template T of OAuthContextInterface = CredentialsOAuthContext
 */
#[Package('checkout')]
interface ApiContextFactoryInterface
{
    /**
     * @return ApiContext<T>
     */
    public function getApiContext(
        ?string $salesChannelId,
        string $partnerAttributionId = PartnerAttributionId::PAYPAL_CLASSIC,
    ): ApiContext;
}
