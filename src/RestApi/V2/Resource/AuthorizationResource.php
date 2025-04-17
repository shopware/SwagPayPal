<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Resource;

use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Gateway\PaymentGateway;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit\Payments\Authorization;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit\Payments\Capture;
use Swag\PayPal\RestApi\ApiContextFactoryInterface;
use Swag\PayPal\RestApi\Exception\PayPalApiException;

#[Package('checkout')]
class AuthorizationResource
{
    /**
     * @internal
     */
    public function __construct(
        private readonly PaymentGateway $paymentGateway,
        private readonly ApiContextFactoryInterface $apiContextFactory,
    ) {
    }

    /**
     * @throws PayPalApiException
     */
    public function get(string $authorizationId, string $salesChannelId): Authorization
    {
        return $this->paymentGateway->getAuthorization($authorizationId, $this->apiContextFactory->getApiContext($salesChannelId));
    }

    /**
     * @throws PayPalApiException
     */
    public function capture(
        string $authorizationId,
        Capture $capture,
        string $salesChannelId,
        string $partnerAttributionId,
        bool $minimalResponse = true,
    ): Capture {
        $context = $this->apiContextFactory
            ->getApiContext($salesChannelId)
            ->withPartnerAttributionId($partnerAttributionId)
            ->withPreferRepresentation(!$minimalResponse);

        return $this->paymentGateway->captureAuthorization($authorizationId, $capture, $context);
    }

    /**
     * @throws PayPalApiException
     */
    public function void(string $authorizationId, string $salesChannelId, string $partnerAttributionId): void
    {
        $context = $this->apiContextFactory
            ->getApiContext($salesChannelId)
            ->withPartnerAttributionId($partnerAttributionId);

        $this->paymentGateway->voidAuthorization($authorizationId, $context);
    }
}
