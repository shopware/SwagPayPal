<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

#[OA\Schema(schema: 'swag_paypal_v1_merchant_integrations_product')]
#[Package('checkout')]
class Product extends PayPalApiStruct
{
    public const VETTING_STATUS_APPROVED = 'APPROVED';
    public const VETTING_STATUS_SUBSCRIBED = 'SUBSCRIBED';

    #[OA\Property(type: 'string')]
    protected string $name;

    #[OA\Property(type: 'string')]
    protected string $vettingStatus;

    /**
     * @var string[]
     */
    #[OA\Property(type: 'array', items: new OA\Items(type: 'string'))]
    protected array $capabilities;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getVettingStatus(): string
    {
        return $this->vettingStatus;
    }

    public function setVettingStatus(string $vettingStatus): void
    {
        $this->vettingStatus = $vettingStatus;
    }

    /**
     * @return string[]
     */
    public function getCapabilities(): array
    {
        return $this->capabilities;
    }

    /**
     * @param string[] $capabilities
     */
    public function setCapabilities(array $capabilities): void
    {
        $this->capabilities = $capabilities;
    }
}
