<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Diagnostics\Reachability;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Diagnostics\Reachability\HostResolution;
use Swag\PayPal\Diagnostics\Reachability\HostResolver;
use Swag\PayPal\Diagnostics\Reachability\ResolvedHost;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(HostResolver::class)]
#[CoversClass(ResolvedHost::class)]
class HostResolverTest extends TestCase
{
    /**
     * @param list<string> $addresses
     * @param list<string> $expectedAddresses
     */
    #[DataProvider('addressProvider')]
    public function testClassifiesTheAddresses(array $addresses, HostResolution $resolution, ?array $expectedAddresses = null): void
    {
        $resolved = (new HostResolver(static fn () => $addresses))->resolve('shop.example');

        static::assertSame($resolution, $resolved->resolution);
        static::assertSame($expectedAddresses ?? $addresses, $resolved->addresses);
    }

    public static function addressProvider(): \Generator
    {
        yield 'public IPv4' => [['93.184.216.34'], HostResolution::Public];
        yield 'public IPv6' => [['2606:2800:220:1:248:1893:25c8:1946'], HostResolution::Public];
        yield 'loopback' => [['127.0.0.1'], HostResolution::Local];
        yield 'IPv6 loopback' => [['::1'], HostResolution::Local];
        yield 'link-local' => [['169.254.10.1'], HostResolution::Local];
        yield 'IPv6 link-local' => [['fe80::1'], HostResolution::Local];
        yield 'unspecified' => [['0.0.0.0'], HostResolution::Local];
        yield 'private 10/8' => [['10.1.2.3'], HostResolution::Private];
        yield 'private 172.16/12' => [['172.20.0.5'], HostResolution::Private];
        yield 'private 192.168/16' => [['192.168.1.10'], HostResolution::Private];
        yield 'IPv6 unique local' => [['fd00::1'], HostResolution::Private];
        yield 'carrier-grade NAT' => [['100.64.0.1'], HostResolution::Private];
        // the HTTP client resolves the host again, so any local address may be the one it connects to
        yield 'local beats public and private' => [['93.184.216.34', '10.0.0.1', '127.0.0.1'], HostResolution::Local];
        yield 'link-local among public ones' => [['93.184.216.34', '2606:2800:220:1:248:1893:25c8:1946', '169.254.169.254'], HostResolution::Local];
        yield 'local beats private' => [['10.0.0.1', '127.0.0.1'], HostResolution::Local];
        yield 'public wins over private' => [['10.0.0.1', '93.184.216.34'], HostResolution::Public];
        yield 'no address' => [[], HostResolution::Unresolved];
        yield 'duplicates are removed' => [['10.0.0.1', '10.0.0.1', '10.0.0.2', '10.0.0.1'], HostResolution::Private, ['10.0.0.1', '10.0.0.2']];
    }

    #[DataProvider('literalProvider')]
    public function testIpLiteralsAreClassifiedWithoutALookup(string $host, HostResolution $resolution, string $address): void
    {
        $resolver = new HostResolver(static fn () => static::fail('No DNS lookup expected'));

        $resolved = $resolver->resolve($host);

        static::assertSame($resolution, $resolved->resolution);
        static::assertSame([$address], $resolved->addresses);
    }

    public static function literalProvider(): \Generator
    {
        yield 'IPv4' => ['93.184.216.34', HostResolution::Public, '93.184.216.34'];
        yield 'loopback' => ['127.0.0.1', HostResolution::Local, '127.0.0.1'];
        yield 'private IPv4' => ['192.168.0.2', HostResolution::Private, '192.168.0.2'];
        yield 'bracketed IPv6 loopback' => ['[::1]', HostResolution::Local, '::1'];
        yield 'bracketed public IPv6' => ['[2606:2800:220:1:248:1893:25c8:1946]', HostResolution::Public, '2606:2800:220:1:248:1893:25c8:1946'];
        yield 'IPv6' => ['fd00::1', HostResolution::Private, 'fd00::1'];
    }

    /**
     * @param list<string> $addresses
     */
    #[DataProvider('pinnedAddressProvider')]
    public function testPinsTheFirstPublicAddress(array $addresses, ?string $pinned): void
    {
        $resolved = (new HostResolver(static fn () => $addresses))->resolve('shop.example');

        static::assertSame($pinned, $resolved->pinnedAddress);
    }

    public static function pinnedAddressProvider(): \Generator
    {
        yield 'public' => [['93.184.216.34', '93.184.216.35'], '93.184.216.34'];
        yield 'public after a private one' => [['10.0.0.1', '93.184.216.34'], '93.184.216.34'];
        yield 'private only' => [['10.0.0.1', '10.0.0.2'], '10.0.0.1'];
        yield 'local' => [['93.184.216.34', '127.0.0.1'], '93.184.216.34'];
        yield 'unresolved' => [[], null];
    }

    public function testDoesNotPinAnIpAddress(): void
    {
        static::assertNull((new HostResolver(static fn () => []))->resolve('93.184.216.34')->pinnedAddress);
        static::assertNull((new HostResolver(static fn () => []))->resolve('[2606:2800:220:1:248:1893:25c8:1946]')->pinnedAddress);
    }

    public function testLooksUpTheHost(): void
    {
        $hosts = [];
        $resolver = new HostResolver(static function (string $host) use (&$hosts) {
            $hosts[] = $host;

            return ['93.184.216.34'];
        });

        $resolver->resolve('shop.example');

        static::assertSame(['shop.example'], $hosts);
    }
}
