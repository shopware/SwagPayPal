<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\Reachability;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

/**
 * @internal
 */
#[Package('checkout')]
final class ResolvedHost extends Struct
{
    /**
     * @param list<string> $addresses
     * @param string|null $pinnedAddress the address requests must go to, so the HTTP client doesn't resolve the host again; null for an IP address
     */
    public function __construct(
        public readonly HostResolution $resolution,
        public readonly array $addresses = [],
        public readonly ?string $pinnedAddress = null,
    ) {
    }
}
