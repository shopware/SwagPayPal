<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\ExpressCallback;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainCollection;

/**
 * Anyone can make the shop record a Store API URL with any host, so only the APP_URL and domain hosts get requests.
 *
 * @internal
 */
#[Package('checkout')]
class TrustedHosts
{
    /**
     * @param EntityRepository<SalesChannelDomainCollection> $salesChannelDomainRepository
     */
    public function __construct(
        private readonly EntityRepository $salesChannelDomainRepository,
        private readonly string $appUrl,
    ) {
    }

    public static function hostOf(string $url): ?string
    {
        $host = \parse_url($url, \PHP_URL_HOST);

        return \is_string($host) && $host !== '' ? \strtolower($host) : null;
    }

    public function contains(string $url, Context $context): bool
    {
        $host = self::hostOf($url);
        if ($host === null) {
            return false;
        }

        if ($host === self::hostOf($this->appUrl)) {
            return true;
        }

        $domains = $this->salesChannelDomainRepository->search(
            (new Criteria())->addFilter(new ContainsFilter('url', $host)),
            $context,
        )->getEntities();

        foreach ($domains as $domain) {
            if (self::hostOf($domain->getUrl()) === $host) {
                return true;
            }
        }

        return false;
    }
}
