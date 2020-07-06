<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\PayPal\_fixtures\TestStruct;

use Swag\PayPal\PayPal\PayPalApiStruct;

class Bar extends PayPalApiStruct
{
    /**
     * @var string
     */
    protected $bar;

    protected function setBar(string $bar): void
    {
        $this->bar = $bar;
    }
}
