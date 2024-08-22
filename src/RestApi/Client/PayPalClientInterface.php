<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\Client;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

#[Package('checkout')]
interface PayPalClientInterface
{
    public function sendPostRequest(string $resourceUri, ?PayPalApiStruct $data, array $headers = []): array;

    public function sendGetRequest(string $resourceUri, array $headers = []): array;

    /**
     * @param PayPalApiStruct[] $data
     */
    public function sendPatchRequest(string $resourceUri, array $data, array $headers = []): array;

    public function sendPutRequest(string $resourceUri, PayPalApiStruct $data, array $headers = []): array;

    public function sendDeleteRequest(string $resourceUri, array $headers = []): array;
}
