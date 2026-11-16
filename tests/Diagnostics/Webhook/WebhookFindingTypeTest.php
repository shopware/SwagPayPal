<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Diagnostics\Webhook;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Diagnostics\Severity;
use Swag\PayPal\Diagnostics\Webhook\WebhookFinding;
use Swag\PayPal\Diagnostics\Webhook\WebhookFindingType;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(WebhookFindingType::class)]
#[CoversClass(WebhookFinding::class)]
class WebhookFindingTypeTest extends TestCase
{
    #[DataProvider('severityProvider')]
    public function testSeverity(WebhookFindingType $type, Severity $severity): void
    {
        static::assertSame($severity, $type->severity());
        static::assertSame($severity, (new WebhookFinding($type))->severity);
    }

    public static function severityProvider(): \Generator
    {
        yield 'without a webhook PayPal doesn\'t report payment changes' => [WebhookFindingType::RegistrationMissing, Severity::Error];
        yield 'a webhook for another URL may belong to the live shop' => [WebhookFindingType::RegistrationInvalid, Severity::Warning];
        yield 'a webhook registered with an old token may work again with the current one' => [WebhookFindingType::RegistrationOutdated, Severity::Warning];
        yield 'the registration couldn\'t be checked' => [WebhookFindingType::RegistrationUnknown, Severity::Unknown];
        yield 'a failed webhook reached the shop and broke' => [WebhookFindingType::LastWebhookFailed, Severity::Error];
        yield 'an unknown payment may belong to another shop' => [WebhookFindingType::UnknownTransaction, Severity::Warning];
        yield 'missed events may still be retried by PayPal' => [WebhookFindingType::MissedEvents, Severity::Warning];
    }
}
