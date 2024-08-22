<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api;

use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
final class PosBaseURL
{
    public const OAUTH = 'https://oauth.izettle.com/';
    public const PRODUCTS = 'https://products.izettle.com/';
    public const INVENTORY = 'https://inventory.izettle.com/';
    public const IMAGE = 'https://image.izettle.com/';
    public const SECURE = 'https://secure.izettle.com/';
    public const PUSHER = 'https://pusher.izettle.com/';

    private function __construct()
    {
    }
}
