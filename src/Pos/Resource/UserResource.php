<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Resource;

use Swag\PayPal\Pos\Api\MerchantInformation;
use Swag\PayPal\Pos\Api\PosBaseURL;
use Swag\PayPal\Pos\Api\PosRequestUri;
use Swag\PayPal\Pos\Client\PosClientFactory;

class UserResource
{
    private PosClientFactory $posClientFactory;

    public function __construct(PosClientFactory $posClientFactory)
    {
        $this->posClientFactory = $posClientFactory;
    }

    public function getMerchantInformation(string $apiKey): ?MerchantInformation
    {
        $client = $this->posClientFactory->getPosClient(PosBaseURL::SECURE, $apiKey);

        $response = $client->sendGetRequest(PosRequestUri::MERCHANT_INFORMATION);

        if ($response === null) {
            return null;
        }

        $information = new MerchantInformation();
        $information->assign($response);

        return $information;
    }
}
