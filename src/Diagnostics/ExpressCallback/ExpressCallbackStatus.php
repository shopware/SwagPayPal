<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\ExpressCallback;

use Shopware\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('checkout')]
enum ExpressCallbackStatus: string
{
    case Inactive = 'inactive';
    case LocalEnvironment = 'local_environment';
    case Disabled = 'disabled';
    case Active = 'active';
}
