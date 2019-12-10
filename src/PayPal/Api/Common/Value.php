<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Api\Common;

abstract class Value extends PayPalStruct
{
    /**
     * @var string
     */
    protected $currency;

    /**
     * @var string
     */
    protected $value;

    protected function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    protected function setValue(string $value): void
    {
        $this->value = $value;
    }
}
