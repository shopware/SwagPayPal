<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Struct\Payment\RelatedResources;

class Refund extends RelatedResource
{
    /**
     * @var string;
     */
    private $parentResourceId;

    public function getParentResourceId(): string
    {
        return $this->parentResourceId;
    }

    public function setParentResourceId(string $parentResourceId): void
    {
        $this->parentResourceId = $parentResourceId;
    }

    public static function fromArray(array $data): Refund
    {
        $result = new self();
        $result->prepare($result, $data, ResourceType::REFUND);

        $data['sale_id'] === null ? $result->setParentResourceId($data['capture_id']) : $result->setParentResourceId($data['sale_id']);

        return $result;
    }
}
