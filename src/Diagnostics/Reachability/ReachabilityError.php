<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\Reachability;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Diagnostics\Severity;

/**
 * When several errors apply, the first case wins.
 *
 * @internal
 */
#[Package('checkout')]
enum ReachabilityError: string
{
    case HostUnresolved = 'host_unresolved';
    case HostLocal = 'host_local';
    case CertificateInvalid = 'certificate_invalid';

    /**
     * PayPal doesn't follow redirects
     */
    case Redirected = 'redirected';
    case Denied = 'denied';
    case NotFound = 'not_found';
    case ServerError = 'server_error';
    case UnexpectedAnswer = 'unexpected_answer';

    /**
     * Servers behind NAT often resolve their own domain to private addresses (split DNS)
     */
    case HostPrivate = 'host_private';

    /**
     * Servers often can't reach their own public URL, so the shop may still be reachable from PayPal
     */
    case Timeout = 'timeout';
    case ConnectionFailed = 'connection_failed';

    public function severity(): Severity
    {
        return match ($this) {
            self::HostPrivate, self::Timeout, self::ConnectionFailed => Severity::Warning,
            default => Severity::Error,
        };
    }

    public static function fromResolution(HostResolution $resolution): ?self
    {
        return match ($resolution) {
            HostResolution::Public => null,
            HostResolution::Private => self::HostPrivate,
            HostResolution::Local => self::HostLocal,
            HostResolution::Unresolved => self::HostUnresolved,
        };
    }

    public static function first(?self ...$errors): ?self
    {
        $first = null;
        foreach ($errors as $error) {
            if ($error !== null && ($first === null || \array_search($error, self::cases(), true) < \array_search($first, self::cases(), true))) {
                $first = $error;
            }
        }

        return $first;
    }
}
