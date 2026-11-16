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
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Diagnostics\ExpressCallback\ExpressCallbackFinding;
use Swag\PayPal\Diagnostics\ExpressCallback\ExpressCallbackFindingType;
use Swag\PayPal\Diagnostics\Severity;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(ExpressCallbackFindingType::class)]
#[CoversClass(ExpressCallbackFinding::class)]
class ExpressCallbackFindingTypeTest extends TestCase
{
    #[DataProvider('severityProvider')]
    public function testSeverity(ExpressCallbackFindingType $type, Severity $severity): void
    {
        static::assertSame($severity, $type->severity());
        static::assertSame($severity, (new ExpressCallbackFinding($type))->severity);
    }

    public static function severityProvider(): \Generator
    {
        yield 'a failed callback reached the shop and broke' => [ExpressCallbackFindingType::LastCallbackFailed, Severity::Error];
        yield 'an untrusted proxy is judged from the Administration\'s request' => [ExpressCallbackFindingType::UntrustedProxy, Severity::Warning];
        yield 'sandbox is only unreliable' => [ExpressCallbackFindingType::Sandbox, Severity::Notice];
        yield 'the Store API URL is learned with the first Express Checkout' => [ExpressCallbackFindingType::StoreApiUrlUnknown, Severity::Unknown];
        yield 'the Store API of another host isn\'t tested, so nothing is known' => [ExpressCallbackFindingType::StoreApiHostUnknown, Severity::Unknown];
    }
}
