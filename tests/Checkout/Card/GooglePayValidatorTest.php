<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Card;

use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Card\GooglePayValidator;
use Swag\PayPal\Checkout\Exception\MissingPayloadException;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\Test\Mock\Setting\Service\SystemConfigServiceMock;

/**
 * @internal
 */
#[Package('checkout')]
class GooglePayValidatorTest extends AbstractCardValidatorTestCase
{
    protected function setUp(): void
    {
        $this->systemConfigService = SystemConfigServiceMock::createWithoutCredentials();
        $this->validator = new GooglePayValidator($this->systemConfigService);
    }

    public function testValidationWithMissingCardResultWillThrowException(): void
    {
        $order = (new Order())->assign([
            'id' => 'paypalOrderId',
            'payment_source' => ['google_pay' => ['card' => null]],
        ]);

        $transaction = $this->createMock(SyncPaymentTransactionStruct::class);
        $salesChannelContext = $this->createMock(SalesChannelContext::class);

        static::expectException(MissingPayloadException::class);
        static::expectExceptionMessage('Missing request payload payment_source.google_pay.card to order "paypalOrderId" not found');

        $this->validator->validate($order, $transaction, $salesChannelContext);
    }

    public function testValidationWithMissingAuthenticationResultWillReturnTrue(): void
    {
        $order = (new Order())->assign([
            'id' => 'paypalOrderId',
            'payment_source' => ['google_pay' => ['card' => ['authentication_result' => null]]],
        ]);

        $transaction = $this->createMock(SyncPaymentTransactionStruct::class);
        $salesChannelContext = $this->createMock(SalesChannelContext::class);

        static::assertTrue($this->validator->validate($order, $transaction, $salesChannelContext));
    }
}
