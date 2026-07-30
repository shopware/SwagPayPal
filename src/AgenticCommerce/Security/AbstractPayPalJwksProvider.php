<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgenticCommerce\Security;

use Shopware\Core\Framework\JWT\Struct\JWKCollection;
use Shopware\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('checkout')]
abstract class AbstractPayPalJwksProvider
{
    abstract public function getJwks(bool $refresh = false): JWKCollection;
}
