<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\Cart\Validation\CartValidator;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\PaymentMethodTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Util\PaymentMethodUtil;

class CartValidatorTest extends TestCase
{
    use ServicesTrait;
    use PaymentMethodTrait;

    private CartValidator $validator;

    private PaymentMethodUtil $paymentMethodUtil;

    private AbstractSalesChannelContextFactory $salesChannelContextFactory;

    private SystemConfigService $systemConfig;

    public function setUp(): void
    {
        /** @var CartValidator $validator */
        $validator = $this->getContainer()->get(CartValidator::class);
        $this->validator = $validator;

        /** @var PaymentMethodUtil $paymentMethodUtil */
        $paymentMethodUtil = $this->getContainer()->get(PaymentMethodUtil::class);
        $this->paymentMethodUtil = $paymentMethodUtil;

        /** @var AbstractSalesChannelContextFactory $salesChannelContextFactory */
        $salesChannelContextFactory = $this->getContainer()->get(SalesChannelContextFactory::class);
        $this->salesChannelContextFactory = $salesChannelContextFactory;

        /** @var SystemConfigService $systemConfig */
        $systemConfig = $this->getContainer()->get(SystemConfigService::class);
        $this->systemConfig = $systemConfig;

        foreach ($this->getDefaultConfigData() as $key => $value) {
            $systemConfig->set($key, $value);
        }
    }

    public function tearDown(): void
    {
        $paymentMethodId = $this->paymentMethodUtil->getPayPalPaymentMethodId(Context::createDefaultContext());

        if ($paymentMethodId) {
            $this->removePaymentMethodFromDefaultsSalesChannel($paymentMethodId);
        }
    }

    public function testValidateWithEmptyCart(): void
    {
        $cart = Generator::createCart();
        $cart->getLineItems()->remove('A');
        $cart->getLineItems()->remove('B');
        $cart->setPrice($this->getEmptyCartPrice());

        $context = $this->getSalesChannelContext();
        $errors = new ErrorCollection();

        $this->validator->validate($cart, $errors, $context);

        static::assertEmpty($errors->getElements());
    }

    public function testValidateWithCartWithValueZero(): void
    {
        $cart = Generator::createCart();
        $cart->getLineItems()->remove('A');
        $cart->setPrice($this->getEmptyCartPrice());

        $context = $this->getSalesChannelContext();
        $errors = new ErrorCollection();

        $this->validator->validate($cart, $errors, $context);

        static::assertCount(1, $errors->getElements());
    }

    public function testValidateWithCartWithValueZeroButPayPalNotActive(): void
    {
        $cart = Generator::createCart();
        $cart->getLineItems()->remove('A');
        $cart->setPrice($this->getEmptyCartPrice());

        $context = Generator::createSalesChannelContext();
        $errors = new ErrorCollection();

        $this->validator->validate($cart, $errors, $context);

        static::assertEmpty($errors->getElements());
    }

    public function testValidateWithNormalCart(): void
    {
        $cart = Generator::createCart();
        $context = $this->getSalesChannelContext();
        $errors = new ErrorCollection();

        $this->validator->validate($cart, $errors, $context);

        static::assertEmpty($errors->getElements());
    }

    public function testValidateWithInvalidCredentials(): void
    {
        $cart = Generator::createCart();
        $context = $this->getSalesChannelContext();
        $errors = new ErrorCollection();

        $this->systemConfig->delete(Settings::CLIENT_ID);
        $this->systemConfig->delete(Settings::CLIENT_SECRET);

        $this->validator->validate($cart, $errors, $context);

        static::assertCount(1, $errors->getElements());
    }

    private function getSalesChannelContext(): SalesChannelContext
    {
        $paymentMethodId = $this->paymentMethodUtil->getPayPalPaymentMethodId(Context::createDefaultContext());

        if ($paymentMethodId) {
            $this->addPaymentMethodToDefaultsSalesChannel($paymentMethodId);
        }

        return $this->salesChannelContextFactory->create(
            Uuid::randomHex(),
            Defaults::SALES_CHANNEL,
            [SalesChannelContextService::PAYMENT_METHOD_ID => $paymentMethodId]
        );
    }
}
