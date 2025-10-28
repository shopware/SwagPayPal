<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Struct\V1\Referral;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

/**
 * @experimental
 */
#[Package('checkout')]
#[OA\Schema(schema: 'paypal_agentic_commerce_v1_referral_measurements')]
class Measurements extends Struct
{
    #[OA\Property(type: 'string')]
    protected string $chest;

    #[OA\Property(type: 'string')]
    protected string $waist;

    #[OA\Property(type: 'string')]
    protected string $height;

    #[OA\Property(type: 'string')]
    protected string $weight;

    public function getChest(): string
    {
        return $this->chest;
    }

    public function setChest(string $chest): void
    {
        $this->chest = $chest;
    }

    public function getWaist(): string
    {
        return $this->waist;
    }

    public function setWaist(string $waist): void
    {
        $this->waist = $waist;
    }

    public function getHeight(): string
    {
        return $this->height;
    }

    public function setHeight(string $height): void
    {
        $this->height = $height;
    }

    public function getWeight(): string
    {
        return $this->weight;
    }

    public function setWeight(string $weight): void
    {
        $this->weight = $weight;
    }
}
