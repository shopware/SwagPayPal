<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Common;

use Swag\PayPal\RestApi\PayPalApiStruct;

abstract class Link extends PayPalApiStruct
{
    /**
     * @var string
     */
    protected $href;

    /**
     * @var string
     */
    protected $rel;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var string|null
     */
    protected $encType;

    public function getHref(): string
    {
        return $this->href;
    }

    public function setHref(string $href): void
    {
        $this->href = $href;
    }

    public function getRel(): string
    {
        return $this->rel;
    }

    public function setRel(string $rel): void
    {
        $this->rel = $rel;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    public function getEncType(): ?string
    {
        return $this->encType;
    }

    public function setEncType(?string $encType): void
    {
        $this->encType = $encType;
    }
}
