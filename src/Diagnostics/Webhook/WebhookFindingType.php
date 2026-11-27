<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\Webhook;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Diagnostics\Severity;

/**
 * @internal
 */
#[Package('checkout')]
enum WebhookFindingType: string
{
    case RegistrationMissing = 'registration_missing';
    case RegistrationInvalid = 'registration_invalid';

    /**
     * Registered for an expected URL, but with another token
     */
    case RegistrationOutdated = 'registration_outdated';
    case RegistrationUnknown = 'registration_unknown';
    case LastWebhookFailed = 'last_webhook_failed';

    /**
     * Typical when the PayPal account is connected to several shops
     */
    case UnknownTransaction = 'unknown_transaction';
    case MissedEvents = 'missed_events';

    public function severity(): Severity
    {
        return match ($this) {
            self::RegistrationMissing, self::LastWebhookFailed => Severity::Error,
            self::RegistrationInvalid, self::RegistrationOutdated, self::UnknownTransaction, self::MissedEvents => Severity::Warning,
            self::RegistrationUnknown => Severity::Unknown,
        };
    }
}
