<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Resource;

use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Gateway\CustomerGateway;
use Shopware\PayPalSDK\Struct\V1\Disputes;
use Shopware\PayPalSDK\Struct\V1\Disputes\Item;
use Swag\PayPal\RestApi\ApiContextFactoryInterface;
use Swag\PayPal\RestApi\Exception\PayPalApiException;

#[Package('checkout')]
class DisputeResource
{
    /**
     * @internal
     */
    public function __construct(
        private readonly CustomerGateway $customerGateway,
        private readonly ApiContextFactoryInterface $apiContextFactory,
    ) {
    }

    /**
     * @throws PayPalApiException
     */
    public function list(?string $salesChannelId, ?string $disputeStateFilter = null): Disputes
    {
        $context = $this->apiContextFactory->getApiContext($salesChannelId);

        if ($disputeStateFilter !== null) {
            $context = $context->withQueryParameter('dispute_state', $disputeStateFilter);
        }

        return $this->customerGateway->getDisputes($context);
    }

    /**
     * @throws PayPalApiException
     */
    public function get(string $disputeId, ?string $salesChannelId): Item
    {
        return $this->customerGateway->getDispute($disputeId, $this->apiContextFactory->getApiContext($salesChannelId));
    }
}
