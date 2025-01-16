<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Card;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Swag\PayPal\Checkout\Card\ApplePayValidator;
use Swag\PayPal\Checkout\Exception\MissingPayloadException;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\Test\Mock\Setting\Service\SystemConfigServiceMock;

/**
 * @internal
 */
class ApplePayValidatorTest extends AbstractCardValidatorTestCase
{
    protected function setUp(): void
    {
        $this->systemConfigService = SystemConfigServiceMock::createWithoutCredentials();
        $this->validator = new ApplePayValidator($this->systemConfigService);
    }

    public function testValidationWithMissingCardResultWillThrowException(): void
    {
        $order = (new Order())->assign([
            'id' => 'paypalOrderId',
            'payment_source' => ['apple_pay' => ['card' => null]],
        ]);

        $transaction = new OrderTransactionEntity();

        static::expectException(MissingPayloadException::class);
        static::expectExceptionMessage('Missing request payload payment_source.apple_pay.card to order "paypalOrderId" not found');

        $this->validator->validate($order, $transaction, Context::createDefaultContext());
    }

    public function testValidationWithMissingAuthenticationResultWillReturnTrue(): void
    {
        $order = (new Order())->assign([
            'id' => 'paypalOrderId',
            'payment_source' => ['apple_pay' => ['card' => ['authentication_result' => null]]],
        ]);

        $transaction = new OrderTransactionEntity();

        static::assertTrue($this->validator->validate($order, $transaction, Context::createDefaultContext()));
    }
}
