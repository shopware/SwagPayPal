<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Api;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;
use Swag\PayPal\PayPal\Api\Payment\ApplicationContext;
use Swag\PayPal\PayPal\Api\Payment\Link;
use Swag\PayPal\PayPal\Api\Payment\Payer;
use Swag\PayPal\PayPal\Api\Payment\PaymentInstruction;
use Swag\PayPal\PayPal\Api\Payment\RedirectUrls;
use Swag\PayPal\PayPal\Api\Payment\Transaction;

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

    /**
     * @var PaymentInstruction|null
     */
    private $paymentInstruction;

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
     * @param Transaction[] $transactions
     */
    public function setTransactions(array $transactions): void
    {
        $this->transactions = $transactions;
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

    public function getPayer(): Payer
    {
        return $this->payer;
    }

    public function setPayer(Payer $payer): void
    {
        $this->payer = $payer;
    }

    public function setRedirectUrls(RedirectUrls $redirectUrls): void
    {
        $this->redirectUrls = $redirectUrls;
    }

    public function getApplicationContext(): ApplicationContext
    {
        return $this->applicationContext;
    }

    public function setApplicationContext(ApplicationContext $applicationContext): void
    {
        $this->applicationContext = $applicationContext;
    }

    public function getPaymentInstruction(): ?PaymentInstruction
    {
        return $this->paymentInstruction;
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

    protected function setPaymentInstruction(PaymentInstruction $paymentInstruction): void
    {
        $this->paymentInstruction = $paymentInstruction;
    }
}
