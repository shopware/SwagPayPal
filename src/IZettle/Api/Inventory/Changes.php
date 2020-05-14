<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Api\Inventory;

use Swag\PayPal\IZettle\Api\Common\IZettleStruct;
use Swag\PayPal\IZettle\Api\Inventory\Changes\Change;

class Changes extends IZettleStruct
{
    /**
     * @var string|null
     */
    protected $returnBalanceForLocationUuid;

    /**
     * @var Change[]
     */
    protected $changes = [];

    public function getReturnBalanceForLocationUuid(): ?string
    {
        return $this->returnBalanceForLocationUuid;
    }

    public function setReturnBalanceForLocationUuid(string $returnBalanceForLocationUuid): void
    {
        $this->returnBalanceForLocationUuid = $returnBalanceForLocationUuid;
    }

    /**
     * @return Change[]
     */
    public function getChanges(): array
    {
        return $this->changes;
    }

    public function addChange(Change ...$changes): void
    {
        foreach ($changes as $change) {
            $this->changes[] = $change;
        }
    }

    /**
     * @param Change[] $changes
     */
    protected function setChanges(array $changes): void
    {
        $this->changes = $changes;
    }
}
