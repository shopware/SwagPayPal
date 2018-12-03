<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\PayPal\Payment;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use SwagPayPal\PayPal\Api\Payment;
use SwagPayPal\PayPal\Payment\PaymentBuilderService;
use SwagPayPal\Test\Helper\PaymentTransactionTrait;
use SwagPayPal\Test\Mock\Repositories\LanguageRepoMock;
use SwagPayPal\Test\Mock\Repositories\SalesChannelRepoMock;
use SwagPayPal\Test\Mock\Setting\SettingsProviderMock;

class PaymentBuilderServiceTest extends TestCase
{
    use PaymentTransactionTrait;

    public function testGetPayment(): void
    {
        $paymentBuilder = $this->createPaymentBuilder();

        $paymentTransaction = $this->createPaymentTransactionStruct();
        $context = Context::createDefaultContext();

        $payment = $paymentBuilder->getPayment($paymentTransaction, $context);

        self::assertInstanceOf(Payment::class, $payment);

        $transaction = $payment->getTransactions()[0];
        self::assertInstanceOf(Payment\Transaction::class, $transaction);
    }

    private function createPaymentBuilder(): PaymentBuilderService
    {
        return new PaymentBuilderService(
            new LanguageRepoMock(),
            new SalesChannelRepoMock(),
            new SettingsProviderMock()
        );
    }
}
