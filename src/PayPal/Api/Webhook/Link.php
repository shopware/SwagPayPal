<?php declare(strict_types=1);

namespace Swag\PayPal\PayPal\Api\Webhook;

use Swag\PayPal\PayPal\Api\Common\Link as CommonLink;

class Link extends CommonLink
{
    /**
     * @var string
     */
    private $encType;

    protected function setEncType(string $encType): void
    {
        $this->encType = $encType;
    }
}
