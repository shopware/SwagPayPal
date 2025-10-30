<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api;

use OpenApi\Attributes as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\CreateWebhooks\CreateWebhooksCollection;

#[OA\Schema(schema: 'paypal_v1_webhook_list')]
class CreateWebhooksList extends PayPalApiStruct
{
    #[OA\Property(type: 'array', items: new OA\Items(ref: CreateWebhooks::class))]
    protected CreateWebhooksCollection $webhooks;

    public function getWebhooks(): CreateWebhooksCollection
    {
        return $this->webhooks;
    }

    public function setWebhooks(CreateWebhooksCollection $webhooks): void
    {
        $this->webhooks = $webhooks;
    }
}
