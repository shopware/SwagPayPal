<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook;

use Shopware\Core\Framework\Context;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Webhook as WebhookV1;
use Swag\PayPal\RestApi\V2\Api\Webhook as WebhookV2;

interface WebhookHandler
{
    /**
     * Returns the name of the webhook event. Defines which webhook event this handler could handle
     *
     * @see WebhookEventTypes
     */
    public function getEventType(): string;

    /**
     * Invokes the webhook using the provided data.
     *
     * @param WebhookV1|WebhookV2 $webhook
     */
    public function invoke(PayPalApiStruct $webhook, Context $context): void;
}
