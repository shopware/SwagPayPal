<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Api;

use SwagPayPal\PayPal\Api\Payment\ApplicationContext;
use SwagPayPal\PayPal\Api\Payment\Link;
use SwagPayPal\PayPal\Api\Payment\Payer;
use SwagPayPal\PayPal\Api\Payment\RedirectUrls;
use SwagPayPal\PayPal\Api\Payment\Transaction;

class Payment extends PayPalStruct
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $intent;

    /**
     * @var string
     */
    protected $state;

    /**
     * @var string
     */
    protected $cart;

    /**
     * @var Payer
     */
    protected $payer;

    /**
     * @var Transaction[]
     */
    protected $transactions;

    /**
     * @var string
     */
    protected $createTime;

    /**
     * @var string
     */
    protected $updateTime;

    /**
     * @var Link[]
     */
    protected $links;

    /**
     * @var RedirectUrls
     */
    protected $redirectUrls;

    /**
     * @var ApplicationContext
     */
    protected $applicationContext;

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return Transaction[]
     */
    public function getTransactions(): array
    {
        return $this->transactions;
    }

    /**
     * @return Link[]
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    public function getIntent(): string
    {
        return $this->intent;
    }

    public function setIntent(string $intent): void
    {
        $this->intent = $intent;
    }

    public function setPayer(Payer $payer): void
    {
        $this->payer = $payer;
    }

    /**
     * @param Transaction[] $transactions
     */
    public function setTransactions(array $transactions): void
    {
        $this->transactions = $transactions;
    }

    public function setRedirectUrls(RedirectUrls $redirectUrls): void
    {
        $this->redirectUrls = $redirectUrls;
    }

    public function setApplicationContext(ApplicationContext $applicationContext): void
    {
        $this->applicationContext = $applicationContext;
    }

    protected function setId(string $id): void
    {
        $this->id = $id;
    }

    protected function setState(string $state): void
    {
        $this->state = $state;
    }

    protected function setCart(string $cart): void
    {
        $this->cart = $cart;
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
