<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Api\Common;

use SwagPayPal\PayPal\Api\PayPalStruct;

abstract class Link extends PayPalStruct
{
    /**
     * @var string
     */
    private $href;

    /**
     * @var string
     */
    private $rel;

    /**
     * @var string
     */
    private $method;

    public function getHref(): string
    {
        return $this->href;
    }

    protected function setHref(string $href): void
    {
        $this->href = $href;
    }

    protected function setRel(string $rel): void
    {
        $this->rel = $rel;
    }

    protected function setMethod(string $method): void
    {
        $this->method = $method;
    }
}
