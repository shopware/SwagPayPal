<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\Webhook\Api;

use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Struct\Collection;
use Shopware\PayPalSDK\Struct\V1\Webhook\Event;

/**
 * @internal
 *
 * @extends Collection<Event>
 */
#[Package('checkout')]
class WebhookEventCollection extends Collection
{
    public static function getExpectedClass(): string
    {
        return Event::class;
    }
}
