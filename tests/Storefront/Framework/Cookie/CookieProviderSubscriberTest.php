<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Storefront\Framework\Cookie;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Cookie\Event\CookieGroupCollectEvent;
use Shopware\Core\Content\Cookie\Struct\CookieEntry;
use Shopware\Core\Content\Cookie\Struct\CookieEntryCollection;
use Shopware\Core\Content\Cookie\Struct\CookieGroup;
use Shopware\Core\Content\Cookie\Struct\CookieGroupCollection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Payment\Method\GooglePayHandler;
use Swag\PayPal\Storefront\Framework\Cookie\CookieProviderSubscriber;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(CookieProviderSubscriber::class)]
#[Package('checkout')]
class CookieProviderSubscriberTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testLoadService(): void
    {
        $subscriber = $this->getContainer()->get(CookieProviderSubscriber::class);

        static::assertInstanceOf(CookieProviderSubscriber::class, $subscriber);
    }

    #[DataProvider('dataStructs')]
    public function testRequiredStructsNotSet(?CookieGroupCollection $cookieGroupCollection): void
    {
        // @deprecated tag:v11.0.0 - class exists with v6.7.3.0.
        if (!$cookieGroupCollection) {
            static::markTestSkipped('CookieGroupCollectEvent does not exist');
        }

        $paymentMethodUtil = $this->createMock(PaymentMethodUtil::class);
        $paymentMethodUtil
            ->expects($this->never())
            ->method('isPaymentMethodActive');

        (new CookieProviderSubscriber($paymentMethodUtil))
            ->onCookieGroupCollect(new CookieGroupCollectEvent(
                $cookieGroupCollection,
                new Request(),
                $this->createMock(SalesChannelContext::class)
            ));

        $entries = $cookieGroupCollection->get('cookie.groupRequired')?->getEntries();

        static::assertNull($entries);
    }

    public static function dataStructs(): array
    {
        // @deprecated tag:v11.0.0 - class exists with v6.7.3.0.
        if (!\class_exists(CookieGroupCollectEvent::class)) {
            return ['class does not exist' => [null]];
        }

        $groupCollection = new CookieGroupCollection();
        $groupCollection->add(new CookieGroup('cookie.groupRequired'));

        $wrongGroupCollection = new CookieGroupCollection();
        $wrongGroupCollection->add(new CookieGroup('cookie.groupNotRequired'));

        return [
            'empty Collection' => [new CookieGroupCollection()],
            'empty group' => [$groupCollection],
            'wrong group' => [$wrongGroupCollection],
        ];
    }

    public function testNoPaymentMethodFound(): void
    {
        // @deprecated tag:v11.0.0 - class exists with v6.7.3.0.
        if (!\class_exists(CookieGroupCollectEvent::class)) {
            static::markTestSkipped('CookieGroupCollectEvent does not exist');
        }

        $group = new CookieGroup('cookie.groupRequired');
        $group->setEntries(new CookieEntryCollection());

        $groupCollection = new CookieGroupCollection();
        $groupCollection->add($group);

        $paymentMethodUtil = $this->createMock(PaymentMethodUtil::class);
        $paymentMethodUtil
            ->expects($this->exactly(2))
            ->method('isPaymentMethodActive')
            ->willReturn(false);

        (new CookieProviderSubscriber($paymentMethodUtil))
            ->onCookieGroupCollect(new CookieGroupCollectEvent(
                $groupCollection,
                new Request(),
                $this->createMock(SalesChannelContext::class)
            ));

        $entries = $groupCollection->get('cookie.groupRequired')?->getEntries();

        static::assertNotNull($entries);
        static::assertFalse($entries->has('paypal-cookie-key'));
        static::assertFalse($entries->has('paypal-google-pay-cookie-key'));
    }

    public function testActiveGooglePayment(): void
    {
        // @deprecated tag:v11.0.0 - class exists with v6.7.3.0.
        if (!\class_exists(CookieGroupCollectEvent::class)) {
            static::markTestSkipped('CookieGroupCollectEvent does not exist');
        }

        $group = new CookieGroup('cookie.groupRequired');
        $group->setEntries(new CookieEntryCollection());

        $groupCollection = new CookieGroupCollection();
        $groupCollection->add($group);

        $paymentMethodUtil = $this->createMock(PaymentMethodUtil::class);
        $paymentMethodUtil
            ->expects($this->exactly(1))
            ->method('isPaymentMethodActive')
            ->with(static::isInstanceOf(SalesChannelContext::class), [GooglePayHandler::class])
            ->willReturn(true);

        (new CookieProviderSubscriber($paymentMethodUtil))
            ->onCookieGroupCollect(new CookieGroupCollectEvent(
                $groupCollection,
                new Request(),
                $this->createMock(SalesChannelContext::class)
            ));

        $entries = $groupCollection->get('cookie.groupRequired')?->getEntries();

        static::assertNotNull($entries);
        static::assertInstanceOf(CookieEntry::class, $payPal = $entries->get('paypal-cookie-key'));
        static::assertInstanceOf(CookieEntry::class, $google = $entries->get('paypal-google-pay-cookie-key'));

        static::assertSame('paypal.cookie.name', $payPal->name);
        static::assertSame('paypal.cookie.googlePay', $google->name);
    }

    public function testActivePayPalPayment(): void
    {
        // @deprecated tag:v11.0.0 - class exists with v6.7.3.0.
        if (!\class_exists(CookieGroupCollectEvent::class)) {
            static::markTestSkipped('CookieGroupCollectEvent does not exist');
        }

        $group = new CookieGroup('cookie.groupRequired');
        $group->setEntries(new CookieEntryCollection());

        $groupCollection = new CookieGroupCollection();
        $groupCollection->add($group);

        $paymentMethodUtil = $this->createMock(PaymentMethodUtil::class);
        $paymentMethodUtil
            ->expects($this->exactly(2))
            ->method('isPaymentMethodActive')
            ->willReturnCallback(static function (SalesChannelContext $context, array $handlers) {
                // GooglePayHandler is checked first
                return \count($handlers) > 1;
            });

        (new CookieProviderSubscriber($paymentMethodUtil))
            ->onCookieGroupCollect(new CookieGroupCollectEvent(
                $groupCollection,
                new Request(),
                $this->createMock(SalesChannelContext::class)
            ));

        $entries = $groupCollection->get('cookie.groupRequired')?->getEntries();

        static::assertNotNull($entries);
        static::assertInstanceOf(CookieEntry::class, $payPal = $entries->get('paypal-cookie-key'));
        static::assertNull($entries->get('paypal-google-pay-cookie-key'));

        static::assertSame('paypal.cookie.name', $payPal->name);
    }
}
