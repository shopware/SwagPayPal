<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Resource;

use Swag\PayPal\IZettle\Api\IZettleBaseURL;
use Swag\PayPal\IZettle\Api\IZettleRequestUri;
use Swag\PayPal\IZettle\Api\MerchantInformation;
use Swag\PayPal\IZettle\Client\IZettleClientFactory;

class UserResource
{
    /**
     * @var IZettleClientFactory
     */
    private $iZettleClientFactory;

    public function __construct(IZettleClientFactory $iZettleClientFactory)
    {
        $this->iZettleClientFactory = $iZettleClientFactory;
    }

    public function getMerchantInformation(string $apiKey): ?MerchantInformation
    {
        $client = $this->iZettleClientFactory->createIZettleClient(IZettleBaseURL::SECURE, $apiKey);

        $response = $client->sendGetRequest(IZettleRequestUri::MERCHANT_INFORMATION);

        if ($response === null) {
            return null;
        }

        $information = new MerchantInformation();
        $information->assign($response);

        return $information;
    }
}
