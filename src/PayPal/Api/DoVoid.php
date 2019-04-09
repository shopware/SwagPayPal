<?php declare(strict_types=1);

namespace SwagPayPal\PayPal\Api;

use SwagPayPal\PayPal\Api\Common\PayPalStruct;
use SwagPayPal\PayPal\Api\DoVoid\Amount;
use SwagPayPal\PayPal\Api\DoVoid\Link;

class DoVoid extends PayPalStruct
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var Amount
     */
    private $amount;

    /**
     * @var string
     */
    private $state;

    /**
     * @var string
     */
    private $parentPayment;

    /**
     * @var string
     */
    private $createTime;

    /**
     * @var string
     */
    private $updateTime;

    /**
     * @var Link[]
     */
    private $links;

    protected function setId(string $id): void
    {
        $this->id = $id;
    }

    protected function setAmount(Amount $amount): void
    {
        $this->amount = $amount;
    }

    protected function setState(string $state): void
    {
        $this->state = $state;
    }

    protected function setParentPayment(string $parentPayment): void
    {
        $this->parentPayment = $parentPayment;
    }

    protected function setCreateTime(string $createTime): void
    {
        $this->createTime = $createTime;
    }

    protected function setUpdateTime(string $updateTime): void
    {
        $this->updateTime = $updateTime;
    }

    /**
     * @param Link[] $links
     */
    protected function setLinks(array $links): void
    {
        $this->links = $links;
    }
}
