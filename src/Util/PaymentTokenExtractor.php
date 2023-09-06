<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V1\Api\Payment;

#[Package('checkout')]
class PaymentTokenExtractor
{
    public static function extract(Payment $payment): string
    {
        $token = '';

        foreach ($payment->getLinks() as $link) {
            if (!($link->getRel() === 'approval_url')) {
                continue;
            }

            \preg_match('/EC-\w+/', $link->getHref(), $matches);

            if (!empty($matches)) {
                $token = $matches[0];
            }
        }

        return $token;
    }
}
