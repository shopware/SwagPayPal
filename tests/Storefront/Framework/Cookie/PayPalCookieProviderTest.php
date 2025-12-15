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
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Storefront\Framework\Cookie\CookieProviderInterface;
use Swag\PayPal\Storefront\Framework\Cookie\PayPalCookieProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
#[Package('checkout')]
class PayPalCookieProviderTest extends TestCase
{
    private EntityRepository&MockObject $paymentMethodRepository;

    private RequestStack $requestStack;

    protected function setUp(): void
    {
        $request = new Request();
        $request->attributes->set('sw-sales-channel-id', Uuid::randomHex());

        $this->paymentMethodRepository = $this->createMock(EntityRepository::class);
        $this->requestStack = new RequestStack();
        $this->requestStack->push($request);
    }

    public function testGetCookieGroupsWithEmptyOriginalCookiesReturnsOriginalCookies(): void
    {
        $cookieProviderMock = $this->getMockBuilder(CookieProviderInterface::class)->getMock();
        $cookies = [];
        $cookieProviderMock->expects(static::once())
            ->method('getCookieGroups')
            ->willReturn($cookies);

        $result = (new PayPalCookieProvider($cookieProviderMock, $this->paymentMethodRepository, $this->requestStack))->getCookieGroups();
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

        $result = (new PayPalCookieProvider($cookieProviderMock, $this->paymentMethodRepository, $this->requestStack))->getCookieGroups();
        static::assertSame($cookies, $result);
    }

    /**
     * @dataProvider dataTestGetCookieGroupsWithRequiredCookieGroup
     */
    public function testGetCookieGroupsWithRequiredCookieGroup(array $cookies, bool $payPalCookieAdded): void
    {
        $cookieProviderMock = $this->getMockBuilder(CookieProviderInterface::class)->getMock();
        $cookieProviderMock->expects(static::once())
            ->method('getCookieGroups')
            ->willReturn($cookies);

        $searchResult = new IdSearchResult(0, [Uuid::randomHex() => ['primaryKey' => 'test-id', 'data' => []]], new Criteria(), Context::createDefaultContext());

        $this->paymentMethodRepository->expects($payPalCookieAdded ? static::once() : static::never())
            ->method('searchIds')
            ->willReturnCallback(static function (Criteria $criteria) use ($searchResult) {
                static::assertCount(3, $criteria->getFilters());

                return $searchResult;
            });

        $result = (new PayPalCookieProvider($cookieProviderMock, $this->paymentMethodRepository, $this->requestStack))->getCookieGroups();
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
            // Matching snippet name, missing is required flag
            [
                [
                    [
                        'snippet_name' => 'cookie.groupRequired',
                        'cookie' => 'example-cookie-key',
                    ],
                ],
                false,
            ],

            // Matching snippet name, required flag false
            [
                [
                    [
                        'isRequired' => false,
                        'snippet_name' => 'cookie.groupRequired',
                        'cookie' => 'example-cookie-key',
                    ],
                ],
                false,
            ],

            // Required flag, wrong snippet name
            [
                [
                    [
                        'isRequired' => true,
                        'snippet_name' => 'cookie.someOtherGroup',
                        'cookie' => 'example-cookie-key',
                    ],
                ],
                false,
            ],

            // With required group, without entries
            [
                [
                    [
                        'isRequired' => true,
                        'snippet_name' => 'cookie.groupRequired',
                        'cookie' => 'example-cookie-key',
                    ],
                ],
                false,
            ],

            // With required group, with entries
            [
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
}
