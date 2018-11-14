<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Struct\Payment\RelatedResources;

use SwagPayPal\PayPal\Struct\Common\Link;
use SwagPayPal\PayPal\Struct\Payment\Transactions\Amount;

abstract class RelatedResource
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var Amount
     */
    protected $amount;

    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $parentPayment;

    /**
     * @var Link[]
     */
    protected $links;

    /**
     * @var string
     */
    protected $state;

    /**
     * @var string
     */
    protected $createTime;

    /**
     * @var string
     */
    protected $updateTime;

    public function getAmount(): Amount
    {
        return $this->amount;
    }

    public function setAmount(Amount $amount): void
    {
        $this->amount = $amount;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getParentPayment(): string
    {
        return $this->parentPayment;
    }

    public function setParentPayment(string $parentPayment): void
    {
        $this->parentPayment = $parentPayment;
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

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
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

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @see ResourceType
     */
    protected function prepare(RelatedResource $resource, array $data, string $type): void
    {
        if (array_key_exists('amount', $data)) {
            $resource->setAmount(Amount::fromArray($data['amount']));
        }
        if (array_key_exists('id', $data)) {
            $resource->setId($data['id']);
        }
        if (array_key_exists('state', $data)) {
            $resource->setState($data['state']);
        }
        if (array_key_exists('parent_payment', $data)) {
            $resource->setParentPayment($data['parent_payment']);
        }
        if (array_key_exists('create_time', $data)) {
            $resource->setCreateTime($data['create_time']);
        }
        if (array_key_exists('update_time', $data)) {
            $resource->setUpdateTime($data['update_time']);
        }
        $resource->setType($type);

        $links = [];
        if (array_key_exists('links', $data)) {
            foreach ($data['links'] as $link) {
                $links[] = Link::fromArray($link);
            }
        }

        $resource->setLinks($links);
    }

    private function setType(string $type): void
    {
        $this->type = $type;
    }
}
