<?php declare(strict_types=1);

namespace Swag\PayPal\Payment\Builder\Util;

class PriceFormatter
{
    public function formatPrice(float $price): string
    {
        return (string) round($price, 2);
    }
}
