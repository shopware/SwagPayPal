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
use Swag\PayPal\Diagnostics\Reachability\ReachabilityError;
use Swag\PayPal\Diagnostics\Reachability\ReachabilityResult;
use Swag\PayPal\Diagnostics\Severity;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(ReachabilityResult::class)]
#[CoversClass(ReachabilityError::class)]
class ReachabilityResultTest extends TestCase
{
    #[DataProvider('combinationProvider')]
    public function testCombinesHostAndRequest(HostResolution $resolution, ?ReachabilityError $requestError, ?ReachabilityError $expected): void
    {
        static::assertSame($expected, ReachabilityError::first(ReachabilityError::fromResolution($resolution), $requestError));
    }

    public static function combinationProvider(): \Generator
    {
        $requestErrors = [
            'reached' => [null, [null, ReachabilityError::HostPrivate, ReachabilityError::HostLocal, ReachabilityError::HostUnresolved]],
            'certificate' => [ReachabilityError::CertificateInvalid, [ReachabilityError::CertificateInvalid, ReachabilityError::CertificateInvalid, ReachabilityError::HostLocal, ReachabilityError::HostUnresolved]],
            'not found' => [ReachabilityError::NotFound, [ReachabilityError::NotFound, ReachabilityError::NotFound, ReachabilityError::HostLocal, ReachabilityError::HostUnresolved]],
            'timeout' => [ReachabilityError::Timeout, [ReachabilityError::Timeout, ReachabilityError::HostPrivate, ReachabilityError::HostLocal, ReachabilityError::HostUnresolved]],
            'connection failed' => [ReachabilityError::ConnectionFailed, [ReachabilityError::ConnectionFailed, ReachabilityError::HostPrivate, ReachabilityError::HostLocal, ReachabilityError::HostUnresolved]],
        ];
        $resolutions = [HostResolution::Public, HostResolution::Private, HostResolution::Local, HostResolution::Unresolved];

        foreach ($requestErrors as $name => [$requestError, $expected]) {
            foreach ($resolutions as $i => $resolution) {
                yield $resolution->value . ', ' . $name => [$resolution, $requestError, $expected[$i]];
            }
        }
    }

    public function testFirstWithoutErrors(): void
    {
        static::assertNull(ReachabilityError::first());
        static::assertNull(ReachabilityError::first(null, null));
        static::assertSame(ReachabilityError::Timeout, ReachabilityError::first(null, ReachabilityError::Timeout));
    }

    public function testCasesAreOrderedByPriority(): void
    {
        static::assertSame(ReachabilityError::HostLocal, ReachabilityError::first(ReachabilityError::ConnectionFailed, ReachabilityError::HostLocal, ReachabilityError::Redirected));
        static::assertSame(ReachabilityError::CertificateInvalid, ReachabilityError::first(ReachabilityError::HostPrivate, ReachabilityError::CertificateInvalid));
    }

    #[DataProvider('severityProvider')]
    public function testSeverity(ReachabilityError $error, Severity $severity): void
    {
        static::assertSame($severity, $error->severity());
        static::assertSame($severity, (new ReachabilityResult('https://shop.example', $error, []))->severity);
    }

    public static function severityProvider(): \Generator
    {
        foreach (ReachabilityError::cases() as $error) {
            yield $error->value => [
                $error,
                \in_array($error, [ReachabilityError::HostPrivate, ReachabilityError::Timeout, ReachabilityError::ConnectionFailed], true) ? Severity::Warning : Severity::Error,
            ];
        }
    }

    public function testJsonShape(): void
    {
        $result = new ReachabilityResult(
            'https://shop.example/x',
            ReachabilityError::Redirected,
            ['93.184.216.34', '2606:2800:220:1:248:1893:25c8:1946'],
            301,
            'https://shop.example/y',
        );

        static::assertEquals([
            'url' => 'https://shop.example/x',
            'error' => 'redirected',
            'severity' => 'error',
            'addresses' => ['93.184.216.34', '2606:2800:220:1:248:1893:25c8:1946'],
            'statusCode' => 301,
            'location' => 'https://shop.example/y',
            'detail' => null,
            'extensions' => [],
        ], \json_decode(\json_encode($result, \JSON_THROW_ON_ERROR), true));
    }

    public function testReachableUrlHasNoErrorOrSeverity(): void
    {
        $result = new ReachabilityResult('https://shop.example', null, ['93.184.216.34'], 400);

        static::assertNull($result->error);
        static::assertNull($result->severity);
    }
}
