<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Resource;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\Client\PayPalClientFactoryInterface;
use Swag\PayPal\RestApi\V1\Api\Disputes;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item as DisputeItem;
use Swag\PayPal\RestApi\V1\RequestUriV1;

#[Package('checkout')]
class DisputeResource
{
    private PayPalClientFactoryInterface $payPalClientFactory;

    /**
     * @internal
     */
    public function __construct(PayPalClientFactoryInterface $payPalClientFactory)
    {
        $this->payPalClientFactory = $payPalClientFactory;
    }

    public function list(?string $salesChannelId, ?string $disputeStateFilter = null): Disputes
    {
        $queryParameter = [];
        if ($disputeStateFilter !== null) {
            $queryParameter['dispute_state'] = $disputeStateFilter;
        }
        $requestUri = \sprintf('%s?%s', RequestUriV1::DISPUTES_RESOURCE, \http_build_query($queryParameter));

        $response = $this->payPalClientFactory->getPayPalClient($salesChannelId)->sendGetRequest(
            $requestUri
        );

        return (new Disputes())->assign($response);
    }

    public function get(string $disputeId, ?string $salesChannelId): DisputeItem
    {
        $response = $this->payPalClientFactory->getPayPalClient($salesChannelId)->sendGetRequest(
            \sprintf('%s/%s', RequestUriV1::DISPUTES_RESOURCE, $disputeId)
        );

        return (new DisputeItem())->assign($response);
    }
}
