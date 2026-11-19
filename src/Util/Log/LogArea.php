<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Log;

use Shopware\Core\Framework\Log\Package;

/**
 * Values of `extra.area`, derived from the namespace of the logging class.
 *
 * @internal
 */
#[Package('checkout')]
final class LogArea
{
    public const CHECKOUT = 'checkout';
    public const EXPRESS = 'express';
    public const PUI = 'pui';
    public const STATUS_SYNC = 'status-sync';
    public const TRACKING = 'tracking';
    public const WEBHOOK = 'webhook';
    public const ADMIN = 'admin';
    public const SETTINGS = 'settings';
    public const STOREFRONT = 'storefront';
    public const API = 'api';
    public const AGENTIC_COMMERCE = 'agentic-commerce';
    public const POS = 'pos';
    public const REPORTING = 'reporting';
    public const OTHER = 'other';

    private const NAMESPACE_PREFIX = 'Swag\\PayPal\\';

    /**
     * The first match wins, so more specific namespaces come first.
     */
    private const NAMESPACES = [
        'Checkout\ExpressCheckout' => self::EXPRESS,
        'Checkout\Payment\MessageQueue' => self::STATUS_SYNC,
        'Checkout\Payment\ScheduledTask' => self::STATUS_SYNC,
        'Checkout\Order\Shipping' => self::TRACKING,
        'Checkout\PUI' => self::PUI,
        'Checkout' => self::CHECKOUT,
        'OrdersApi\Builder' => self::CHECKOUT,
        'OrdersApi\Administration' => self::ADMIN,
        'Webhook' => self::WEBHOOK,
        'RestApi' => self::API,
        'Setting' => self::SETTINGS,
        'Storefront' => self::STOREFRONT,
        'Installment' => self::STOREFRONT,
        'AgenticCommerce' => self::AGENTIC_COMMERCE,
        'Pos' => self::POS,
        'Reporting' => self::REPORTING,
    ];

    public static function forClass(string $class): string
    {
        if (!\str_starts_with($class, self::NAMESPACE_PREFIX)) {
            return self::OTHER;
        }

        $namespace = \substr($class, \strlen(self::NAMESPACE_PREFIX));

        foreach (self::NAMESPACES as $prefix => $area) {
            if ($namespace === $prefix || \str_starts_with($namespace, $prefix . '\\')) {
                return $area;
            }
        }

        return self::OTHER;
    }
}
