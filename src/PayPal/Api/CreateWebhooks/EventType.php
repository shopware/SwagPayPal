<?php declare(strict_types=1);

namespace Swag\PayPal\PayPal\Api\CreateWebhooks;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;

class EventType extends PayPalStruct
{
    /**
     * @var string
     */
    protected $name;

    protected function setName(string $name): void
    {
        $this->name = $name;
    }
}
