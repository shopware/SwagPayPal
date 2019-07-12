<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Mock;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EventDispatcherMock implements EventDispatcherInterface
{
    public function dispatch($event)
    {
    }
}
