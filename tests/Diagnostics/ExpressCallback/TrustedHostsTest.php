<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Diagnostics\ExpressCallback;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainCollection;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Swag\PayPal\Diagnostics\ExpressCallback\TrustedHosts;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(TrustedHosts::class)]
class TrustedHostsTest extends TestCase
{
    /**
     * @param list<string> $domainUrls
     */
    #[DataProvider('hostProvider')]
    public function testContains(string $url, string $appUrl, array $domainUrls, bool $trusted): void
    {
        $repository = $this->createRepository([$this->createDomains($domainUrls)]);

        static::assertSame($trusted, (new TrustedHosts($repository, $appUrl))->contains($url, Context::createDefaultContext()));
    }

    /**
     * @return iterable<string, array{string, string, list<string>, bool}>
     */
    public static function hostProvider(): iterable
    {
        yield 'the APP_URL' => ['https://shop.example/store-api', 'https://shop.example', [], true];
        yield 'the APP_URL in another case' => ['https://SHOP.example', 'https://shop.example:8443', [], true];
        yield 'a domain of a Sales Channel' => ['https://api.shop.example/shop', '', ['https://api.shop.example/de', 'https://other.example'], true];
        yield 'a domain with a port' => ['https://api.shop.example', '', ['https://api.shop.example:8443'], true];
        yield 'another host' => ['https://evil.example', 'https://shop.example', ['https://api.shop.example'], false];
        yield 'a host that only contains a domain' => ['https://evil.example', '', ['https://example'], false];
        yield 'a domain that only contains the host' => ['https://shop.example', '', ['https://myshop.example'], false];
        yield 'an internal host' => ['http://169.254.169.254', 'https://shop.example', ['https://api.shop.example'], false];
        yield 'no host' => ['/store-api', '', ['https://api.shop.example'], false];
    }

    public function testLooksUpTheDomainsOfTheHost(): void
    {
        $searchedCriteria = [];
        $repository = $this->createRepository([$this->createDomains(['https://api.shop.example'])], $searchedCriteria);

        (new TrustedHosts($repository, ''))->contains('https://api.shop.example/shop', Context::createDefaultContext());

        $criteria = $searchedCriteria[0] ?? null;
        static::assertNotNull($criteria);
        static::assertEquals([new ContainsFilter('url', 'api.shop.example')], $criteria->getFilters());
    }

    public function testDoesNotLookUpTheDomainsForTheAppUrl(): void
    {
        $searchedCriteria = [];
        $repository = $this->createRepository([new SalesChannelDomainCollection()], $searchedCriteria);

        static::assertTrue((new TrustedHosts($repository, 'https://shop.example'))->contains('https://shop.example/store-api', Context::createDefaultContext()));
        static::assertSame([], $searchedCriteria);
    }

    /**
     * @param list<SalesChannelDomainCollection> $searches
     * @param list<Criteria> $searchedCriteria
     *
     * @return StaticEntityRepository<SalesChannelDomainCollection>
     */
    private function createRepository(array $searches, array &$searchedCriteria = []): StaticEntityRepository
    {
        /** @var StaticEntityRepository<SalesChannelDomainCollection> $repository */
        $repository = new StaticEntityRepository(\array_map(
            static function (SalesChannelDomainCollection $domains) use (&$searchedCriteria): callable {
                return static function (Criteria $criteria) use ($domains, &$searchedCriteria): SalesChannelDomainCollection {
                    $searchedCriteria[] = $criteria;

                    return $domains;
                };
            },
            $searches,
        ));

        return $repository;
    }

    /**
     * @param list<string> $urls
     */
    private function createDomains(array $urls): SalesChannelDomainCollection
    {
        $domains = new SalesChannelDomainCollection();
        foreach ($urls as $url) {
            $domain = new SalesChannelDomainEntity();
            $domain->setId(Uuid::randomHex());
            $domain->setUrl($url);
            $domains->add($domain);
        }

        return $domains;
    }
}
