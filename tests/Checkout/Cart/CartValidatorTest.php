<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Cart;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Test\Generator;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Checkout\Cart\Validation\CartValidator;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\PaymentMethodTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * @internal
 */
#[Package('checkout')]
class CartValidatorTest extends TestCase
{
    use IntegrationTestBehaviour;
    use PaymentMethodTrait;
    use ServicesTrait;

    private CartValidator $validator;

    private PaymentMethodUtil $paymentMethodUtil;

    private AbstractSalesChannelContextFactory $salesChannelContextFactory;

    private SystemConfigService $systemConfig;

    protected function setUp(): void
    {
        $this->validator = $this->getContainer()->get(CartValidator::class);
        $this->paymentMethodUtil = $this->getContainer()->get(PaymentMethodUtil::class);
        $this->salesChannelContextFactory = $this->getContainer()->get(SalesChannelContextFactory::class);
        $this->systemConfig = $this->getContainer()->get(SystemConfigService::class);

        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        /** @var RequestStack $requestStack */
        $requestStack = $this->getContainer()->get('request_stack');
        $requestStack->push($request);

        foreach ($this->getDefaultConfigData() as $key => $value) {
            $this->systemConfig->set($key, $value);
        }
    }

    protected function tearDown(): void
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
        $cart->setPrice(new CartPrice(
            0.0,
            0.0,
            0,
            new CalculatedTaxCollection(),
            new TaxRuleCollection(),
            CartPrice::TAX_STATE_GROSS
        ));

        $context = $this->getSalesChannelContext();
        $errors = new ErrorCollection();

        $this->validator->validate($cart, $errors, $context);

        static::assertEmpty($errors->getElements());
    }

    public function testValidateWithCartWithValueZero(): void
    {
        $cart = Generator::createCart();
        $cart->getLineItems()->remove('A');
        $cart->setPrice(new CartPrice(
            0.0,
            0.0,
            0,
            new CalculatedTaxCollection(),
            new TaxRuleCollection(),
            CartPrice::TAX_STATE_GROSS
        ));

        $context = $this->getSalesChannelContext();
        $errors = new ErrorCollection();

        $this->validator->validate($cart, $errors, $context);

        static::assertCount(1, $errors->getElements());
    }

    public function testValidateWithCartWithValueZeroButPayPalNotActive(): void
    {
        $cart = Generator::createCart();
        $cart->getLineItems()->remove('A');
        $cart->setPrice(new CartPrice(
            0.0,
            0.0,
            0,
            new CalculatedTaxCollection(),
            new TaxRuleCollection(),
            CartPrice::TAX_STATE_GROSS
        ));

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

    public function testValidateWithExcludedProduct(): void
    {
        $cart = Generator::createCart();
        $context = $this->getSalesChannelContext();
        $errors = new ErrorCollection();

        $this->systemConfig->set(Settings::EXCLUDED_PRODUCT_IDS, ['A']);
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
            TestDefaults::SALES_CHANNEL,
            [SalesChannelContextService::PAYMENT_METHOD_ID => $paymentMethodId]
        );
    }
}
