<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront;

use Shopware\Core\Framework\Log\Package;

/**
 * Sent to `frontend.paypal.handle-error`, keep in sync with `Resources/app/storefront/src/base/paypal-plugin.error.ts`,
 * `swag-paypal.abstract-buttons.js` and `page/swag-paypal.express-checkout.js`.
 *
 * @internal
 */
#[Package('checkout')]
final class BrowserErrorCode
{
    public const USER_CANCELLED = 'SWAG_PAYPAL__USER_CANCELLED';
    public const EXPRESS_USER_CANCELLED = 'SWAG_PAYPAL__EXPRESS_USER_CANCELLED';
    public const DECLINED = 'SWAG_PAYPAL__DECLINED_ERROR';
    public const SCRIPT = 'SWAG_PAYPAL__SCRIPT_ERROR';
    public const SCRIPT_NOT_LOADED = 'SWAG_PAYPAL__SCRIPT_NOT_LOADED';
    public const BROWSER_UNSUPPORTED = 'SWAG_PAYPAL__BROWSER_UNSUPPORTED';
    public const NOT_ELIGIBLE = 'SWAG_PAYPAL__NOT_ELIGIBLE';
    public const GENERIC = 'SWAG_PAYPAL__GENERIC_ERROR';
    public const EXPRESS_GENERIC = 'SWAG_PAYPAL__EXPRESS_GENERIC_ERROR';

    // the dependency loader reports a failed script load as a generic error with this step
    public const STEP_SCRIPT_LOAD = 'SCRIPT_LOAD';
}
