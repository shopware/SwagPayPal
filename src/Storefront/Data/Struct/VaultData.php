<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data\Struct;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

#[Package('checkout')]
class VaultData extends Struct
{
    protected ?string $identifier = null;

    protected bool $preselect = false;

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(?string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function isPreselect(): bool
    {
        return $this->preselect;
    }

    public function setPreselect(bool $preselect): void
    {
        $this->preselect = $preselect;
    }
}
