<?php declare(strict_types=1);

namespace Swag\PayPal\Util;

use Swag\PayPal\PayPal\Api\Payment;
use Swag\PayPal\PayPal\Api\Payment\Link;

class PaymentTokenExtractor
{
    public static function extract(Payment $paymentResource): string
    {
        $token = '';

        /** @var Link $link */
        foreach ($paymentResource->getLinks() as $link) {
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
