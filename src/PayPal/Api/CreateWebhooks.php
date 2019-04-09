<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Api;

use SwagPayPal\PayPal\Api\Common\PayPalStruct;
use SwagPayPal\PayPal\Api\CreateWebhooks\EventType;

class CreateWebhooks extends PayPalStruct
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @var EventType[]
     */
    protected $eventTypes;

    protected function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * @param EventType[] $eventTypes
     */
    protected function setEventTypes(array $eventTypes): void
    {
        $this->eventTypes = $eventTypes;
    }
}
