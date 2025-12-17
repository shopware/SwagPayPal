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
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\PlatformRequest;
use Shopware\Storefront\Framework\Cookie\CookieProviderInterface;
use Swag\PayPal\Storefront\Framework\Cookie\GooglePayCookieProvider;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
#[Package('checkout')]
class GooglePayCookieProviderTest extends TestCase
{
    private CookieProviderInterface&MockObject $cookieProvider;

    private PaymentMethodUtil&MockObject $paymentMethodUtil;

    private GooglePayCookieProvider $googlePayCookieProvider;

    protected function setUp(): void
    {
        $request = new Request();
        $request->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT, Generator::createSalesChannelContext());

        $this->googlePayCookieProvider = new GooglePayCookieProvider(
            $this->cookieProvider = $this->createMock(CookieProviderInterface::class),
            $this->paymentMethodUtil = $this->createMock(PaymentMethodUtil::class),
            new RequestStack([$request]),
        );
    }

    public function testGetCookieGroupsWithEmptyOriginalCookiesReturnsOriginalCookies(): void
    {
        $cookies = [];
        $this->cookieProvider->expects(static::once())
            ->method('getCookieGroups')
            ->willReturn($cookies);

        $result = $this->googlePayCookieProvider->getCookieGroups();
        static::assertSame($cookies, $result);
    }

    public function testGetCookieGroupsWithOriginalCookiesNotInSubArraysReturnsOriginalCookies(): void
    {
        $cookies = [
            'snippet_name' => 'cookie.example.name',
            'cookie' => 'example-cookie-key',
        ];
        $this->cookieProvider->expects(static::once())
            ->method('getCookieGroups')
            ->willReturn($cookies);

        $result = $this->googlePayCookieProvider->getCookieGroups();
        static::assertSame($cookies, $result);
    }

    /**
     * @dataProvider dataTestGetCookieGroupsWithRequiredCookieGroup
     */
    public function testGetCookieGroupsWithRequiredCookieGroup(array $cookies, bool $cookieAdded): void
    {
        $this->cookieProvider->expects(static::once())
            ->method('getCookieGroups')
            ->willReturn($cookies);

        $this->paymentMethodUtil
            ->expects($cookieAdded ? static::once() : static::never())
            ->method('isPaymentMethodActive')
            ->willReturn(true);

        $result = $this->googlePayCookieProvider->getCookieGroups();

        if (!$cookieAdded) {
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
        static::assertSame('paypal.cookie.googlePay', $payPalCookie['snippet_name']);
        static::assertArrayHasKey('cookie', $payPalCookie);
        static::assertSame('paypal-google-pay-cookie-key', $payPalCookie['cookie']);
    }

    public static function dataTestGetCookieGroupsWithRequiredCookieGroup(): \Generator
    {
        yield 'Matching snippet name, missing is required flag' => [
            [
                [
                    'snippet_name' => 'cookie.groupRequired',
                    'cookie' => 'example-cookie-key',
                ],
            ],
            false,
        ];

        yield 'Matching snippet name, required flag false' => [
            [
                [
                    'isRequired' => false,
                    'snippet_name' => 'cookie.groupRequired',
                    'cookie' => 'example-cookie-key',
                ],
            ],
            false,
        ];

        yield 'Required flag, wrong snippet name' => [
            [
                [
                    'isRequired' => true,
                    'snippet_name' => 'cookie.someOtherGroup',
                    'cookie' => 'example-cookie-key',
                ],
            ],
            false,
        ];

        yield 'With required group, without entries' => [
            [
                [
                    'isRequired' => true,
                    'snippet_name' => 'cookie.groupRequired',
                    'cookie' => 'example-cookie-key',
                ],
            ],
            false,
        ];

        yield 'With required group, with entries' => [
            [
                [
                    'isRequired' => true,
                    'snippet_name' => 'cookie.groupRequired',
                    'cookie' => 'example-cookie-key',
                    'entries' => [],
                ],
            ],
            true,
        ];
    }

    public function testEarlyReturnsEmptyCookieGroups(): void
    {
        $cookies = [
            'isRequired' => true,
            'snippet_name' => 'cookie.groupRequired',
            'cookie' => 'example-cookie-key',
            'entries' => [],
        ];

        $this->cookieProvider->expects(static::once())
            ->method('getCookieGroups')
            ->willReturn($cookies);

        $result = $this->googlePayCookieProvider->getCookieGroups();

        static::assertSame($cookies, $result);
    }
}
