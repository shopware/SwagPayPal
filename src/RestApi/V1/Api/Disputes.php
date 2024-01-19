<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Common\LinkCollection;
use Swag\PayPal\RestApi\V1\Api\Disputes\ItemCollection;

/**
 * @OA\Schema(schema="swag_paypal_v1_disputes")
 */
#[Package('checkout')]
class Disputes extends PayPalApiStruct
{
    /**
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_disputes_item"}, nullable=true)
     */
    protected ?ItemCollection $items = null;

    /**
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_common_link"})
     */
    protected LinkCollection $links;

    public function getItems(): ?ItemCollection
    {
        return $this->items;
    }

    public function setItems(?ItemCollection $items): void
    {
        $this->items = $items;
    }

    public function getLinks(): LinkCollection
    {
        return $this->links;
    }

    public function setLinks(LinkCollection $links): void
    {
        $this->links = $links;
    }
}
