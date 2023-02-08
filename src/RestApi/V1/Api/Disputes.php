<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item;
use Swag\PayPal\RestApi\V1\Api\Disputes\Link;

/**
 * @OA\Schema(schema="swag_paypal_v1_disputes")
 */
class Disputes extends PayPalApiStruct
{
    /**
     * @var Item[]|null
     *
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_disputes_item"}, nullable=true)
     */
    protected ?array $items = null;

    /**
     * @var Link[]
     *
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_common_link"})
     */
    protected array $links;

    /**
     * @return Item[]|null
     */
    public function getItems(): ?array
    {
        return $this->items;
    }

    /**
     * @param Item[]|null $items
     */
    public function setItems(?array $items): void
    {
        $this->items = $items;
    }

    /**
     * @return Link[]
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    /**
     * @param Link[] $links
     */
    public function setLinks(array $links): void
    {
        $this->links = $links;
    }
}
