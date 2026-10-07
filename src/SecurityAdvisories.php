<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal;

use Shopware\Core\Framework\Log\Package;

/**
 * List of all fixed security advisories
 */
#[Package('checkout')]
class SecurityAdvisories
{
    public const ADVISORIES = [
        'GHSAmwvm68w432gq' => [
            'description' => 'PayPal order ID can be reused for another order',
            'link' => 'https://github.com/shopware/SwagPayPal/security/advisories/GHSA-mwvm-68w4-32gq',
        ],
    ];

    /**
     * Check if an advisory is fixed, case insensitive and ignoring dashes
     */
    public static function isFixed(string $advisoryId): bool
    {
        $advisoryId = \str_replace('-', '', $advisoryId);

        foreach (self::ADVISORIES as $id => $_) {
            if (\strcasecmp($advisoryId, $id) === 0) {
                return true;
            }
        }

        return false;
    }
}
