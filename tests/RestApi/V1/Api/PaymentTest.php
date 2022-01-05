<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi\V1\Api;

use PHPUnit\Framework\TestCase;
use Swag\PayPal\RestApi\V1\Api\Payment;

class PaymentTest extends TestCase
{
    public function testJsonSerializable(): void
    {
        $payment = new Payment();
        $payment->assign([
            'redirect_urls' => ['return_url' => 'return'],
        ]);

        static::assertSame(
            '{"intent":"sale","redirect_urls":{"return_url":"return"}}',
            \json_encode($payment)
        );
    }
}
