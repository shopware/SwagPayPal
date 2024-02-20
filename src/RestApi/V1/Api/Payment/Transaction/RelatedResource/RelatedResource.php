<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Payment\Transaction\RelatedResource;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Common\Amount;
use Swag\PayPal\RestApi\V1\Api\Common\Link;
use Swag\PayPal\RestApi\V1\Api\Common\LinkCollection;

#[Package('checkout')]
abstract class RelatedResource extends PayPalApiStruct
{
    #[OA\Property(type: 'string')]
    protected string $id;

    #[OA\Property(type: 'string')]
    protected string $state;

    #[OA\Property(ref: Amount::class)]
    protected Amount $amount;

    #[OA\Property(type: 'string')]
    protected string $paymentMode;

    #[OA\Property(type: 'string')]
    protected string $createTime;

    #[OA\Property(type: 'string')]
    protected string $updateTime;

    #[OA\Property(type: 'string')]
    protected string $protectionEligibility;

    #[OA\Property(type: 'string')]
    protected string $protectionEligibilityType;

    #[OA\Property(type: 'string')]
    protected string $receiptId;

    #[OA\Property(type: 'string')]
    protected string $parentPayment;

    #[OA\Property(type: 'array', items: new OA\Items(ref: Link::class))]
    protected LinkCollection $links;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = \mb_strtolower($state);
    }

    public function getAmount(): Amount
    {
        return $this->amount;
    }

    public function setAmount(Amount $amount): void
    {
        $this->amount = $amount;
    }

    public function getPaymentMode(): string
    {
        return $this->paymentMode;
    }

    public function setPaymentMode(string $paymentMode): void
    {
        $this->paymentMode = $paymentMode;
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

    public function getProtectionEligibility(): string
    {
        return $this->protectionEligibility;
    }

    public function setProtectionEligibility(string $protectionEligibility): void
    {
        $this->protectionEligibility = $protectionEligibility;
    }

    public function getProtectionEligibilityType(): string
    {
        return $this->protectionEligibilityType;
    }

    public function setProtectionEligibilityType(string $protectionEligibilityType): void
    {
        $this->protectionEligibilityType = $protectionEligibilityType;
    }

    public function getReceiptId(): string
    {
        return $this->receiptId;
    }

    public function setReceiptId(string $receiptId): void
    {
        $this->receiptId = $receiptId;
    }

    public function getParentPayment(): string
    {
        return $this->parentPayment;
    }

    public function setParentPayment(string $parentPayment): void
    {
        $this->parentPayment = $parentPayment;
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
