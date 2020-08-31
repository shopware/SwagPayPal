<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Setting\Struct;

use Shopware\Core\Framework\Struct\Struct;

class ProductCount extends Struct
{
    /**
     * @var int
     */
    protected $localCount;

    /**
     * @var int
     */
    protected $remoteCount;

    public function getLocalCount(): int
    {
        return $this->localCount;
    }

    public function setLocalCount(int $localCount): void
    {
        $this->localCount = $localCount;
    }

    public function getRemoteCount(): int
    {
        return $this->remoteCount;
    }

    public function setRemoteCount(int $remoteCount): void
    {
        $this->remoteCount = $remoteCount;
    }
}
