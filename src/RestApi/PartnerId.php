<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi;

use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
final class PartnerId
{
    public const SANDBOX = '45KXQA7PULGAG';
    public const LIVE = 'DYKPBPEAW5JNA';

    private function __construct()
    {
    }
}
