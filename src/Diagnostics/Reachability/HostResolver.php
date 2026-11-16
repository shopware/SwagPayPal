<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\Reachability;

use Shopware\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Skips the hosts file, which is closer to what PayPal sees.
 *
 * @internal
 */
#[Package('checkout')]
class HostResolver
{
    private const LOCAL_RANGES = [
        '127.0.0.0/8',
        '::1/128',
        '169.254.0.0/16',
        'fe80::/10',
        '0.0.0.0/8',
        '::/128',
    ];

    /**
     * @var \Closure(string): list<string>
     */
    private readonly \Closure $lookup;

    /**
     * @param (\Closure(string): list<string>)|null $lookup
     */
    public function __construct(?\Closure $lookup = null)
    {
        $this->lookup = $lookup ?? $this->lookupDns(...);
    }

    public function resolve(string $host): ResolvedHost
    {
        $literal = \trim($host, '[]');
        $isAddress = \filter_var($literal, \FILTER_VALIDATE_IP) !== false;
        $addresses = $isAddress ? [$literal] : ($this->lookup)($host);
        $addresses = \array_values(\array_unique($addresses));

        $local = [];
        $private = [];
        $public = [];
        foreach ($addresses as $address) {
            if (IpUtils::checkIp($address, self::LOCAL_RANGES)) {
                $local[] = $address;
            } elseif (IpUtils::isPrivateIp($address) || IpUtils::checkIp($address, '100.64.0.0/10')) {
                $private[] = $address;
            } else {
                $public[] = $address;
            }
        }

        // one local address is enough, the HTTP client may connect to it
        $resolution = match (true) {
            $local !== [] => HostResolution::Local,
            $public !== [] => HostResolution::Public,
            $private !== [] => HostResolution::Private,
            default => HostResolution::Unresolved,
        };

        return new ResolvedHost($resolution, $addresses, $isAddress ? null : ($public[0] ?? $private[0] ?? null));
    }

    /**
     * @return list<string>
     */
    private function lookupDns(string $host): array
    {
        $records = @\dns_get_record($host, \DNS_A | \DNS_AAAA);
        if ($records === false) {
            return [];
        }

        $addresses = [];
        foreach ($records as $record) {
            $address = $record['ip'] ?? $record['ipv6'] ?? null;
            if (\is_string($address)) {
                $addresses[] = $address;
            }
        }

        return $addresses;
    }
}
