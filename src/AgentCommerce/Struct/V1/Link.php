<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Struct\V1;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

/**
 * @experimental
 */
#[Package('checkout')]
#[OA\Schema(
    schema: 'paypal_agentic_commerce_v1_link',
    required: ['rel', 'href']
)]
class Link extends PayPalApiStruct
{
    public const REL__SELF = 'self';
    public const REL__UPDATE = 'update';
    public const REL__CHECKOUT = 'checkout';

    public const METHOD__GET = 'GET';
    public const METHOD__POST = 'POST';
    public const METHOD__PUT = 'PUT';

    /**
     * Link relationship type
     */
    #[OA\Property(
        type: 'string',
        enum: [self::REL__SELF, self::REL__UPDATE, self::REL__CHECKOUT]
    )]
    protected string $rel;

    /**
     * Target URL for the link
     *
     * example: https://your-domain.com/api/paypal/v1/merchant-cart/CART-123
     */
    #[OA\Property(type: 'string')]
    protected string $href;

    /**
     * HTTP method for the link
     */
    #[OA\Property(
        type: 'string',
        enum: [self::METHOD__GET, self::METHOD__POST, self::METHOD__PUT],
    )]
    protected ?string $method = null;

    /**
     * Human-readable description of the link
     */
    #[OA\Property(type: 'string')]
    protected ?string $title = null;

    /**
     * Expected content type
     */
    #[OA\Property(type: 'string')]
    protected ?string $type = null;

    public function getRel(): string
    {
        return $this->rel;
    }

    public function setRel(string $rel): void
    {
        if (!\in_array($rel, [self::REL__SELF, self::REL__UPDATE, self::REL__CHECKOUT], true)) {
            throw new \InvalidArgumentException(\sprintf('Rel "%s" is not valid.', $rel));
        }

        $this->rel = $rel;
    }

    public function getHref(): string
    {
        return $this->href;
    }

    public function setHref(string $href): void
    {
        $this->href = $href;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function setMethod(?string $method): void
    {
        if (!\in_array($method, [self::METHOD__GET, self::METHOD__POST, self::METHOD__PUT], true)) {
            throw new \InvalidArgumentException(\sprintf('Method "%s" is not valid.', $method));
        }

        $this->method = $method;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function jsonSerialize(): array
    {
        return \array_filter(parent::jsonSerialize());
    }
}
