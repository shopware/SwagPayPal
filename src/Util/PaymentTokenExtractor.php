<?php declare(strict_types=1);

namespace Swag\PayPal\Util;

use Swag\PayPal\PayPal\Api\Payment;

class PaymentTokenExtractor
{
    public static function extract(Payment $payment): string
    {
        $token = '';

        foreach ($payment->getLinks() as $link) {
            if (!($link->getRel() === 'approval_url')) {
                continue;
            }

            preg_match('/EC-\w+/', $link->getHref(), $matches);

            if (!empty($matches)) {
                $token = $matches[0];
            }
        }

        return $token;
    }
}
