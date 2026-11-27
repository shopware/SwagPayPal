<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\ExpressCallback;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Diagnostics\Severity;

/**
 * @internal
 */
#[Package('checkout')]
enum ExpressCallbackFindingType: string
{
    case LastCallbackFailed = 'last_callback_failed';

    /**
     * Without trusted proxies, Symfony builds `http://` URLs, and PayPal doesn't follow them to HTTPS
     */
    case UntrustedProxy = 'untrusted_proxy';

    case Sandbox = 'sandbox';

    /**
     * Headless frontends call the Store API on a host only the first Express Checkout reveals
     */
    case StoreApiUrlUnknown = 'store_api_url_unknown';

    /**
     * Anyone can make the shop record a Store API URL, so the diagnostics don't send a request to a host that isn't the shop's
     */
    case StoreApiHostUnknown = 'store_api_host_unknown';

    public function severity(): Severity
    {
        return match ($this) {
            self::LastCallbackFailed => Severity::Error,
            self::UntrustedProxy => Severity::Warning,
            self::Sandbox => Severity::Notice,
            self::StoreApiUrlUnknown, self::StoreApiHostUnknown => Severity::Unknown,
        };
    }
}
