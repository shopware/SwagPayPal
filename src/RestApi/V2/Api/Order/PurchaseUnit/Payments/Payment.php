<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Common\Link;
use Swag\PayPal\RestApi\V2\Api\Common\LinkCollection;
use Swag\PayPal\RestApi\V2\Api\Common\Money;

#[Package('checkout')]
abstract class Payment extends PayPalApiStruct
{
    public const MAX_LENGTH_INVOICE_ID = 127;
    public const MAX_LENGTH_NOTE_TO_PAYER = 255;

    #[OA\Property(type: 'string')]
    protected string $status;

    #[OA\Property(type: 'string')]
    protected string $id;

    #[OA\Property(ref: Money::class, nullable: true)]
    protected ?Money $amount = null;

    #[OA\Property(type: 'string', nullable: true)]
    protected ?string $customId = null;

    #[OA\Property(type: 'array', items: new OA\Items(ref: Link::class))]
    protected LinkCollection $links;

    #[OA\Property(type: 'string')]
    protected string $createTime;

    #[OA\Property(type: 'string')]
    protected string $updateTime;

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getAmount(): ?Money
    {
        return $this->amount;
    }

    public function setAmount(?Money $amount): void
    {
        $this->amount = $amount;
    }

    public function getCustomId(): ?string
    {
        return $this->customId;
    }

    public function setCustomId(?string $customId): void
    {
        $this->customId = $customId;
    }

    public function getLinks(): LinkCollection
    {
        return $this->links;
    }

    public function setLinks(LinkCollection $links): void
    {
        $this->links = $links;
    }

    public function getCreateTime(): string
    {
        return $this->createTime;
    }

    public function setCreateTime(string $createTime): void
    {
        $this->createTime = $createTime;
    }

    public function getUpdateTime(): string
    {
        return $this->updateTime;
    }

    public function setUpdateTime(string $updateTime): void
    {
        $this->updateTime = $updateTime;
    }
}
