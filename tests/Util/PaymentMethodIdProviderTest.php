<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Util;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Swag\PayPal\Test\Mock\Repositories\PaymentMethodRepoMock;
use Swag\PayPal\Util\PaymentMethodIdProvider;

class PaymentMethodIdProviderTest extends TestCase
{
    /**
     * @var PaymentMethodIdProvider
     */
    private $paymentMethodIdProvider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentMethodIdProvider = new PaymentMethodIdProvider(new PaymentMethodRepoMock());
    }

    public function testGetPayPalPaymentMethodId(): void
    {
        $paymentMethodId = $this->paymentMethodIdProvider->getPayPalPaymentMethodId(Context::createDefaultContext());

        static::assertSame(PaymentMethodRepoMock::PAYPAL_PAYMENT_METHOD_ID, $paymentMethodId);
    }

    public function testGetPayPalPaymentMethodIdWithWrongHandler(): void
    {
        $context = $this->getContextWithoutPaymentId();
        $paymentMethodId = $this->paymentMethodIdProvider->getPayPalPaymentMethodId($context);

        static::assertNull($paymentMethodId);
    }

    private function getContextWithoutPaymentId(): Context
    {
        $defaultContext = Context::createDefaultContext();

        return new Context(
            $defaultContext->getSource(),
            $defaultContext->getRuleIds(),
            $defaultContext->getCurrencyId(),
            $defaultContext->getLanguageIdChain(),
            PaymentMethodRepoMock::VERSION_ID_WITHOUT_PAYMENT_METHOD,
            $defaultContext->getCurrencyFactor(),
            $defaultContext->getCurrencyPrecision(),
            $defaultContext->considerInheritance(),
            $defaultContext->getTaxState()
        );
    }
}
