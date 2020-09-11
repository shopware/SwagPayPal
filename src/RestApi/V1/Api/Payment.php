<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api;

use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Payment\ApplicationContext;
use Swag\PayPal\RestApi\V1\Api\Payment\Link;
use Swag\PayPal\RestApi\V1\Api\Payment\Payer;
use Swag\PayPal\RestApi\V1\Api\Payment\PaymentInstruction;
use Swag\PayPal\RestApi\V1\Api\Payment\RedirectUrls;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction;

class Payment extends PayPalApiStruct
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
    protected $paymentInstruction;

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

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getCart(): string
    {
        return $this->cart;
    }

    public function setCart(string $cart): void
    {
        $this->cart = $cart;
    }

    public function getPayer(): Payer
    {
        return $this->payer;
    }

    public function setPayer(Payer $payer): void
    {
        $this->payer = $payer;
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

    public function getRedirectUrls(): RedirectUrls
    {
        return $this->redirectUrls;
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

    public function setPaymentInstruction(?PaymentInstruction $paymentInstruction): void
    {
        $this->paymentInstruction = $paymentInstruction;
    }

    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();

        unset($data['payment_instruction']);

        return $data;
    }
}
