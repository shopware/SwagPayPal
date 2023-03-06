<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Api\Common\_fixtures\TestStruct;

use Swag\PayPal\Pos\Api\Common\PosStruct;

/**
 * @internal
 */
class Bar extends PosStruct
{
    protected string $bar;

    protected function setBar(string $bar): void
    {
        $this->bar = $bar;
    }
}
