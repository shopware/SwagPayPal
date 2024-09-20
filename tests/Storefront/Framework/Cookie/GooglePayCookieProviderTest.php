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
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Storefront\Framework\Cookie\CookieProviderInterface;
use Swag\PayPal\Storefront\Framework\Cookie\GooglePayCookieProvider;

/**
 * @internal
 */
#[Package('checkout')]
class GooglePayCookieProviderTest extends TestCase
{
    private EntityRepository&MockObject $paymentMethodRepository;

    protected function setUp(): void
    {
        $this->paymentMethodRepository = $this->createMock(EntityRepository::class);
    }

    public function testGetCookieGroupsWithEmptyOriginalCookiesReturnsOriginalCookies(): void
    {
        $cookieProviderMock = $this->getMockBuilder(CookieProviderInterface::class)->getMock();
        $cookies = [];
        $cookieProviderMock->expects(static::once())
            ->method('getCookieGroups')
            ->willReturn($cookies);

        $result = (new GooglePayCookieProvider($cookieProviderMock, $this->paymentMethodRepository))->getCookieGroups();
        static::assertSame($cookies, $result);
    }

    public function testGetCookieGroupsWithOriginalCookiesNotInSubArraysReturnsOriginalCookies(): void
    {
        $cookieProviderMock = $this->getMockBuilder(CookieProviderInterface::class)->getMock();
        $cookies = [
            'snippet_name' => 'cookie.example.name',
            'cookie' => 'example-cookie-key',
        ];
        $cookieProviderMock->expects(static::once())
            ->method('getCookieGroups')
            ->willReturn($cookies);

        $result = (new GooglePayCookieProvider($cookieProviderMock, $this->paymentMethodRepository))->getCookieGroups();
        static::assertSame($cookies, $result);
    }

    /**
     * @dataProvider dataTestGetCookieGroupsWithRequiredCookieGroup
     */
    public function testGetCookieGroupsWithRequiredCookieGroup(array $cookies, bool $cookieAdded): void
    {
        $cookieProviderMock = $this->getMockBuilder(CookieProviderInterface::class)->getMock();
        $cookieProviderMock->expects(static::once())
            ->method('getCookieGroups')
            ->willReturn($cookies);

        $searchResult = new IdSearchResult(0, [['primaryKey' => 'test-id', 'data' => []]], new Criteria(), Context::createDefaultContext());

        $this->paymentMethodRepository->expects($cookieAdded ? static::once() : static::never())
                ->method('searchIds')
                ->willReturn($searchResult);

        $result = (new GooglePayCookieProvider($cookieProviderMock, $this->paymentMethodRepository))->getCookieGroups();

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
}
