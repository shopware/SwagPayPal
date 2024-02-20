<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Common;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

#[OA\Schema(schema: 'swag_paypal_v1_disputes_common_service_details')]
#[Package('checkout')]
class ServiceDetails extends PayPalApiStruct
{
    #[OA\Property(type: 'string')]
    protected string $description;

    #[OA\Property(type: 'string')]
    protected string $serviceStarted;

    #[OA\Property(type: 'string')]
    protected string $note;

    #[OA\Property(type: 'array', items: new OA\Items(ref: SubReason::class))]
    protected SubReasonCollection $subReasons;

    #[OA\Property(type: 'string')]
    protected string $purchaseUrl;

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getServiceStarted(): string
    {
        return $this->serviceStarted;
    }

    public function setServiceStarted(string $serviceStarted): void
    {
        $this->serviceStarted = $serviceStarted;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function setNote(string $note): void
    {
        $this->note = $note;
    }

    public function getSubReasons(): SubReasonCollection
    {
        return $this->subReasons;
    }

    public function setSubReasons(SubReasonCollection $subReasons): void
    {
        $this->subReasons = $subReasons;
    }

    public function getPurchaseUrl(): string
    {
        return $this->purchaseUrl;
    }

    public function setPurchaseUrl(string $purchaseUrl): void
    {
        $this->purchaseUrl = $purchaseUrl;
    }
}
