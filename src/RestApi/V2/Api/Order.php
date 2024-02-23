<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Common\Link;
use Swag\PayPal\RestApi\V2\Api\Common\LinkCollection;
use Swag\PayPal\RestApi\V2\Api\Order\ApplicationContext;
use Swag\PayPal\RestApi\V2\Api\Order\Payer;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnitCollection;
use Swag\PayPal\RestApi\V2\PaymentIntentV2;

#[OA\Schema(schema: 'swag_paypal_v2_order')]
#[Package('checkout')]
class Order extends PayPalApiStruct
{
    public const PROCESSING_INSTRUCTION_COMPLETE_ON_APPROVAL = 'ORDER_COMPLETE_ON_PAYMENT_APPROVAL';

    #[OA\Property(type: 'string')]
    protected string $createTime;

    #[OA\Property(type: 'string')]
    protected string $updateTime;

    #[OA\Property(type: 'string')]
    protected string $id;

    #[OA\Property(type: 'string')]
    protected string $intent = PaymentIntentV2::CAPTURE;

    #[OA\Property(ref: Payer::class)]
    protected Payer $payer;

    #[OA\Property(type: 'array', items: new OA\Items(ref: PurchaseUnit::class), nullable: true)]
    protected ?PurchaseUnitCollection $purchaseUnits;

    #[OA\Property(ref: ApplicationContext::class)]
    protected ApplicationContext $applicationContext;

    #[OA\Property(ref: PaymentSource::class, nullable: true)]
    protected ?PaymentSource $paymentSource = null;

    #[OA\Property(type: 'string')]
    protected string $status;

    #[OA\Property(type: 'string')]
    protected string $processingInstruction;

    #[OA\Property(type: 'array', items: new OA\Items(ref: Link::class))]
    protected LinkCollection $links;

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

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getIntent(): string
    {
        return $this->intent;
    }

    public function setIntent(string $intent): void
    {
        $this->intent = $intent;
    }

    public function getPayer(): Payer
    {
        return $this->payer;
    }

    public function setPayer(Payer $payer): void
    {
        $this->payer = $payer;
    }

    public function getPurchaseUnits(): PurchaseUnitCollection
    {
        return $this->purchaseUnits ?? $this->purchaseUnits = new PurchaseUnitCollection();
    }

    public function setPurchaseUnits(PurchaseUnitCollection $purchaseUnits): void
    {
        $this->purchaseUnits = $purchaseUnits;
    }

    public function getApplicationContext(): ApplicationContext
    {
        return $this->applicationContext;
    }

    public function setApplicationContext(ApplicationContext $applicationContext): void
    {
        $this->applicationContext = $applicationContext;
    }

    public function getPaymentSource(): ?PaymentSource
    {
        return $this->paymentSource;
    }

    public function setPaymentSource(?PaymentSource $paymentSource): void
    {
        $this->paymentSource = $paymentSource;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getProcessingInstruction(): string
    {
        return $this->processingInstruction;
    }

    public function setProcessingInstruction(string $processingInstruction): void
    {
        $this->processingInstruction = $processingInstruction;
    }

    public function getLinks(): LinkCollection
    {
        return $this->links;
    }

    public function setLinks(LinkCollection $links): void
    {
        $this->links = $links;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return \array_filter(parent::jsonSerialize());
    }
}
