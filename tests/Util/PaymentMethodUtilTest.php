<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
    public const SALESCHANNEL_WITHOUT_PAYPAL_PAYMENT_METHOD = '4ce46b49d1904a5db0b41573e9355b51';

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * @var SalesChannelRepoMock
     */
    private $salesChannelRepoMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salesChannelRepoMock = new SalesChannelRepoMock();
        $this->paymentMethodUtil = new PaymentMethodUtil(new PaymentMethodRepoMock(), $this->salesChannelRepoMock);
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
        static::assertTrue($this->paymentMethodUtil->isPaypalPaymentMethodInSalesChannel($salesChannelContext));
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
        static::assertFalse($this->paymentMethodUtil->isPaypalPaymentMethodInSalesChannel($salesChannelContext));
    }

    public function testSetPayPalAsDefaultPaymentMethodForASpecificSalesChannel(): void
    {
        $context = Context::createDefaultContext();
        $this->paymentMethodUtil->setPayPalAsDefaultPaymentMethod($context, Defaults::SALES_CHANNEL);
        $this->assertPaymentMethodUpdate($context);
    }

    public function testSetPayPalAsDefaultPaymentWithoutBeingPresentForTheRequestedSalesChannel(): void
    {
        $context = Context::createDefaultContext();
        $this->paymentMethodUtil->setPayPalAsDefaultPaymentMethod($context, self::SALESCHANNEL_WITHOUT_PAYPAL_PAYMENT_METHOD);
        $this->assertPaymentMethodUpdate($context, false);
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

    private function assertPaymentMethodUpdate(Context $context, bool $paypalPaymentMethodPresent = true): void
    {
        $updates = $this->salesChannelRepoMock->getUpdateData();
        static::assertCount(1, $updates);
        $updateData = $updates[0];
        static::assertCount($paypalPaymentMethodPresent ? 2 : 3, $updateData);
        static::assertArrayHasKey('id', $updateData);
        static::assertSame($paypalPaymentMethodPresent ? Defaults::SALES_CHANNEL : self::SALESCHANNEL_WITHOUT_PAYPAL_PAYMENT_METHOD, $updateData['id']);
        static::assertArrayHasKey('paymentMethodId', $updateData);
        $payPalPaymentMethodId = $this->paymentMethodUtil->getPayPalPaymentMethodId($context);
        static::assertNotNull($payPalPaymentMethodId);
        static::assertSame($payPalPaymentMethodId, $updateData['paymentMethodId']);
    }
}
