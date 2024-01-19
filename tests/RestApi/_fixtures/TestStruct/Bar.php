<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi\_fixtures\TestStruct;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

/**
 * @internal
 */
#[Package('checkout')]
class Bar extends PayPalApiStruct
{
    protected string $bar;

    protected function setBar(string $bar): void
    {
        $this->bar = $bar;
    }
}
