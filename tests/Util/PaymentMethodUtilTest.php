<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Util;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Test\Mock\Repositories\PaymentMethodRepoMock;
use Swag\PayPal\Test\Mock\Repositories\SalesChannelRepoMock;
use Swag\PayPal\Util\PaymentMethodUtil;

class PaymentMethodUtilTest extends TestCase
{
    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentMethodUtil = new PaymentMethodUtil(new PaymentMethodRepoMock(), new SalesChannelRepoMock());
    }

    public function testGetPayPalPaymentMethodId(): void
    {
        $paymentMethodId = $this->paymentMethodUtil->getPayPalPaymentMethodId(Context::createDefaultContext());

        static::assertSame(PaymentMethodRepoMock::PAYPAL_PAYMENT_METHOD_ID, $paymentMethodId);
    }

    public function testGetPayPalPaymentMethodIdWithWrongHandler(): void
    {
        $context = $this->getContextWithoutPaymentId();
        $paymentMethodId = $this->paymentMethodUtil->getPayPalPaymentMethodId($context);

        static::assertNull($paymentMethodId);
    }

    public function testGetPaypalPaymentMethodInSalesChannel(): void
    {
        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId(Defaults::SALES_CHANNEL);
        $salesChannelContext = Generator::createSalesChannelContext(
            null,
            null,
            null,
            $salesChannel
        );
        static::assertTrue($this->paymentMethodUtil->getPaypalPaymentMethodInSalesChannel($salesChannelContext));
    }

    public function testGetPaypalPaymentMethodInSalesChannelWithoutPayPalPaymentMethodId(): void
    {
        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId(Defaults::SALES_CHANNEL);
        $baseContext = Context::createDefaultContext();
        $baseContext->assign([
            'versionId' => PaymentMethodRepoMock::VERSION_ID_WITHOUT_PAYMENT_METHOD,
        ]);
        $salesChannelContext = Generator::createSalesChannelContext(
            $baseContext,
            null,
            null,
            $salesChannel
        );
        static::assertFalse($this->paymentMethodUtil->getPaypalPaymentMethodInSalesChannel($salesChannelContext));
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
