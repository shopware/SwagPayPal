<?php declare(strict_types=1);

namespace Swag\PayPal\PayPal\Api\Common;

abstract class Amount extends PayPalStruct
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

    public function setTotal(string $total): void
    {
        $this->total = $total;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function setDetails(Details $details): void
    {
        $this->details = $details;
    }
}
