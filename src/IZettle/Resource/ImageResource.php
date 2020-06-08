<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Resource;

use Swag\PayPal\IZettle\Api\Image\BulkImageUpload;
use Swag\PayPal\IZettle\Api\Image\BulkImageUploadResponse;
use Swag\PayPal\IZettle\Api\IZettleBaseURL;
use Swag\PayPal\IZettle\Api\IZettleRequestUri;
use Swag\PayPal\IZettle\Client\IZettleClientFactory;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelEntity;

class ImageResource
{
    /**
     * @var IZettleClientFactory
     */
    private $iZettleClientFactory;

    public function __construct(IZettleClientFactory $iZettleClientFactory)
    {
        $this->iZettleClientFactory = $iZettleClientFactory;
    }

    public function bulkUploadPictures(
        IZettleSalesChannelEntity $salesChannelEntity,
        BulkImageUpload $bulkProductImageUpload
    ): ?BulkImageUploadResponse {
        // TODO: Refactor to API key auth
        $username = $salesChannelEntity->getUsername();
        $password = $salesChannelEntity->getPassword();
        $client = $this->iZettleClientFactory->createIZettleClient(IZettleBaseURL::IMAGE, $username, $password);

        $response = $client->sendPostRequest(IZettleRequestUri::IMAGE_RESOURCE_BULK, $bulkProductImageUpload);

        if ($response === null) {
            return null;
        }

        $images = new BulkImageUploadResponse();
        $images->assign($response);

        return $images;
    }
}
