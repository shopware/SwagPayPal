<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Api\Common\_fixtures\TestStruct;

use Swag\PayPal\Pos\Api\Common\PosStruct;

class Foo extends PosStruct
{
    protected string $fooBaz;

    protected string $fooBoo;

    protected function setFooBaz(string $fooBaz): void
    {
        $this->fooBaz = $fooBaz;
    }

    protected function setFooBoo(string $fooBoo): void
    {
        $this->fooBoo = $fooBoo;
    }
}
