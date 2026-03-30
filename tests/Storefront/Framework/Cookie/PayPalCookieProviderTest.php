<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Storefront\Framework\Cookie;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Cookie\Event\CookieGroupCollectEvent;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\PlatformRequest;
use Shopware\Core\Test\Generator;
use Shopware\Storefront\Framework\Cookie\CookieProviderInterface;
use Swag\PayPal\Storefront\Framework\Cookie\PayPalCookieProvider;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 *
 * @deprecated tag:v11.0.0 - Will be removed. Use {@see CookieGroupCollectEvent} instead to introduce cookies.
 */
#[Package('checkout')]
class PayPalCookieProviderTest extends TestCase
{
    private CookieProviderInterface&MockObject $cookieProvider;

    private PaymentMethodUtil&MockObject $paymentMethodUtil;

    private PayPalCookieProvider $payPalCookieProvider;

    protected function setUp(): void
    {
        $request = new Request();
        $request->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT, Generator::generateSalesChannelContext());

        $this->payPalCookieProvider = new PayPalCookieProvider(
            $this->cookieProvider = $this->createMock(CookieProviderInterface::class),
            $this->paymentMethodUtil = $this->createMock(PaymentMethodUtil::class),
            new RequestStack([$request]),
        );
    }

    public function testGetCookieGroupsWithEmptyOriginalCookiesReturnsOriginalCookies(): void
    {
        if (\class_exists(CookieGroupCollectEvent::class)) {
            static::markTestSkipped('Deprecated. Logic moved to Swag\PayPal\Storefront\Framework\Cookie\CookieProviderSubscriber');
        }

        $cookies = [];
        $this->cookieProvider->expects($this->once())
            ->method('getCookieGroups')
            ->willReturn($cookies);

        $result = $this->payPalCookieProvider->getCookieGroups();
        static::assertSame($cookies, $result);
    }

    public function testGetCookieGroupsWithOriginalCookiesNotInSubArraysReturnsOriginalCookies(): void
    {
        if (\class_exists(CookieGroupCollectEvent::class)) {
            static::markTestSkipped('Deprecated. Logic moved to Swag\PayPal\Storefront\Framework\Cookie\CookieProviderSubscriber');
        }

        $cookies = [
            'snippet_name' => 'cookie.example.name',
            'cookie' => 'example-cookie-key',
        ];
        $this->cookieProvider->expects($this->once())
            ->method('getCookieGroups')
            ->willReturn($cookies);

        $result = $this->payPalCookieProvider->getCookieGroups();
        static::assertSame($cookies, $result);
    }

    #[DataProvider('dataTestGetCookieGroupsWithRequiredCookieGroup')]
    public function testGetCookieGroupsWithRequiredCookieGroup(array $cookies, bool $payPalCookieAdded): void
    {
        if (\class_exists(CookieGroupCollectEvent::class)) {
            static::markTestSkipped('Deprecated. Logic moved to Swag\PayPal\Storefront\Framework\Cookie\CookieProviderSubscriber');
        }

        $this->cookieProvider->expects($this->once())
            ->method('getCookieGroups')
            ->willReturn($cookies);

        $this->paymentMethodUtil
            ->expects($payPalCookieAdded ? $this->once() : $this->never())
            ->method('isPaymentMethodActive')
            ->willReturn(true);

        $result = $this->payPalCookieProvider->getCookieGroups();
        if (!$payPalCookieAdded) {
            static::assertSame($cookies, $result);

            return;
        }

        static::assertCount(1, $result);
        static::assertArrayHasKey('entries', $result[0]);
        $entries = $result[0]['entries'];
        static::assertCount(1, $entries);
        $payPalCookie = $entries[0];
        static::assertIsArray($payPalCookie);
        static::assertArrayHasKey('snippet_name', $payPalCookie);
        static::assertSame('paypal.cookie.name', $payPalCookie['snippet_name']);
        static::assertArrayHasKey('cookie', $payPalCookie);
        static::assertSame('paypal-cookie-key', $payPalCookie['cookie']);
    }

    public static function dataTestGetCookieGroupsWithRequiredCookieGroup(): array
    {
        return [
            'Matching snippet name, missing is required flag' => [
                [
                    [
                        'snippet_name' => 'cookie.groupRequired',
                        'cookie' => 'example-cookie-key',
                    ],
                ],
                false,
            ],

            'Matching snippet name, required flag false' => [
                [
                    [
                        'isRequired' => false,
                        'snippet_name' => 'cookie.groupRequired',
                        'cookie' => 'example-cookie-key',
                    ],
                ],
                false,
            ],

            'Required flag, wrong snippet name' => [
                [
                    [
                        'isRequired' => true,
                        'snippet_name' => 'cookie.someOtherGroup',
                        'cookie' => 'example-cookie-key',
                    ],
                ],
                false,
            ],

            'With required group, without entries' => [
                [
                    [
                        'isRequired' => true,
                        'snippet_name' => 'cookie.groupRequired',
                        'cookie' => 'example-cookie-key',
                    ],
                ],
                false,
            ],

            'With required group, with entries' => [
                [
                    [
                        'isRequired' => true,
                        'snippet_name' => 'cookie.groupRequired',
                        'cookie' => 'example-cookie-key',
                        'entries' => [],
                    ],
                ],
                true,
            ],
        ];
    }

    public function testEarlyReturnsEmptyCookieGroups(): void
    {
        if (!\class_exists(CookieGroupCollectEvent::class)) {
            static::markTestSkipped('Deprecated. Logic moved to Swag\PayPal\Storefront\Framework\Cookie\CookieProviderSubscriber');
        }

        $cookies = [
            'isRequired' => true,
            'snippet_name' => 'cookie.groupRequired',
            'cookie' => 'example-cookie-key',
            'entries' => [],
        ];

        $this->cookieProvider->expects($this->once())
            ->method('getCookieGroups')
            ->willReturn($cookies);

        $result = $this->payPalCookieProvider->getCookieGroups();

        static::assertSame($cookies, $result);
    }
}
