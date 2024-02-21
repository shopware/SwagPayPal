<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Setting\Struct;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

#[OA\Schema(schema: 'swag_paypal_pos_setting_product_count')]
#[Package('checkout')]
class ProductCount extends Struct
{
    #[OA\Property(type: 'integer')]
    protected int $localCount;

    #[OA\Property(type: 'integer')]
    protected int $remoteCount;

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
