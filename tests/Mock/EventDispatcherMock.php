<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock;

use Shopware\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('checkout')]
class EventDispatcherMock implements EventDispatcherInterface
{
    private ?object $lastEvent = null;

    public function dispatch(object $event, ?string $eventName = null): object
    {
        $this->lastEvent = $event;

        return $event;
    }

    public function getLastEvent(): ?object
    {
        return $this->lastEvent;
    }
}
