<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Resource;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Image\BulkImageUpload;
use Swag\PayPal\Pos\Api\Image\BulkImageUploadResponse;
use Swag\PayPal\Pos\Api\PosBaseURL;
use Swag\PayPal\Pos\Api\PosRequestUri;
use Swag\PayPal\Pos\Client\PosClientFactory;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelEntity;

#[Package('checkout')]
class ImageResource
{
    private PosClientFactory $posClientFactory;

    /**
     * @internal
     */
    public function __construct(PosClientFactory $posClientFactory)
    {
        $this->posClientFactory = $posClientFactory;
    }

    public function bulkUploadPictures(
        PosSalesChannelEntity $salesChannelEntity,
        BulkImageUpload $bulkProductImageUpload,
    ): ?BulkImageUploadResponse {
        $apiKey = $salesChannelEntity->getApiKey();
        $client = $this->posClientFactory->getPosClient(PosBaseURL::IMAGE, $apiKey);

        $response = $client->sendPostRequest(PosRequestUri::IMAGE_RESOURCE_BULK, $bulkProductImageUpload);

        if ($response === null) {
            return null;
        }

        $images = new BulkImageUploadResponse();
        $images->assign($response);

        return $images;
    }
}
