<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Common;

use Swag\PayPal\RestApi\PayPalApiStruct;

abstract class Amount extends PayPalApiStruct
{
    /**
     * @var string
     */
    protected $total;

    /**
     * @var string
     */
    protected $currency;

    /**
     * @var Details
     */
    protected $details;

    public function getDetails(): Details
    {
        return $this->details;
    }

    public function setDetails(Details $details): void
    {
        $this->details = $details;
    }

    public function getTotal(): string
    {
        return $this->total;
    }

    public function setTotal(string $total): void
    {
        $this->total = $total;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }
}
