<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Struct;

use SwagPayPal\PayPal\Struct\Common\Link;
use SwagPayPal\PayPal\Struct\Payment\ApplicationContext;
use SwagPayPal\PayPal\Struct\Payment\Credit;
use SwagPayPal\PayPal\Struct\Payment\Payer;
use SwagPayPal\PayPal\Struct\Payment\PaymentInstruction;
use SwagPayPal\PayPal\Struct\Payment\RedirectUrls;
use SwagPayPal\PayPal\Struct\Payment\Transactions;

class Payment
{
    /**
     * @var string
     */
    private $intent;

    /**
     * @var Payer
     */
    private $payer;

    /**
     * @var ApplicationContext
     */
    private $applicationContext;

    /**
     * @var Transactions
     */
    private $transactions;

    /**
     * @var RedirectUrls
     */
    private $redirectUrls;

    /**
     * @var Link[]
     */
    private $links;

    /**
     * @var PaymentInstruction
     */
    private $paymentInstruction;

    /**
     * @var string
     */
    private $state;

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $cart;

    /**
     * @var string
     */
    private $createTime;

    /**
     * @var string
     */
    private $updateTime;

    /**
     * @var Credit
     */
    private $creditFinancingOffered;

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

    public function getApplicationContext(): ApplicationContext
    {
        return $this->applicationContext;
    }

    public function setApplicationContext(ApplicationContext $applicationContext): void
    {
        $this->applicationContext = $applicationContext;
    }

    public function getTransactions(): Transactions
    {
        return $this->transactions;
    }

    public function setTransactions(Transactions $transactions): void
    {
        $this->transactions = $transactions;
    }

    public function getRedirectUrls(): RedirectUrls
    {
        return $this->redirectUrls;
    }

    public function setRedirectUrls(RedirectUrls $redirectUrls): void
    {
        $this->redirectUrls = $redirectUrls;
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

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getCart(): ?string
    {
        return $this->cart;
    }

    public function setCart(string $cart): void
    {
        $this->cart = $cart;
    }

    public function getCreateTime(): ?string
    {
        return $this->createTime;
    }

    public function setCreateTime(string $createTime): void
    {
        $this->createTime = $createTime;
    }

    public function getUpdateTime(): ?string
    {
        return $this->updateTime;
    }

    public function setUpdateTime(string $updateTime): void
    {
        $this->updateTime = $updateTime;
    }

    public function getPaymentInstruction(): PaymentInstruction
    {
        return $this->paymentInstruction;
    }

    public function setPaymentInstruction(PaymentInstruction $paymentInstruction): void
    {
        $this->paymentInstruction = $paymentInstruction;
    }

    public function getCreditFinancingOffered(): Credit
    {
        return $this->creditFinancingOffered;
    }

    public function setCreditFinancingOffered($creditFinancingOffered): void
    {
        $this->creditFinancingOffered = $creditFinancingOffered;
    }

    public static function fromArray(array $data = []): Payment
    {
        $result = new self();

        $result->setIntent($data['intent']);

        if (array_key_exists('cart', $data)) {
            $result->setCart($data['cart']);
        }

        $result->setId($data['id']);
        $result->setState($data['state']);
        $result->setCreateTime($data['create_time']);

        if (array_key_exists('update_time', $data)) {
            $result->setUpdateTime($data['update_time']);
        }

        if (array_key_exists('amount', $data['transactions'][0])) {
            $result->setTransactions(Transactions::fromArray($data['transactions'][0]));
        } else {
            $result->setTransactions(Transactions::fromArray($data['transactions']));
        }

        if (array_key_exists('payment_instruction', $data)) {
            $result->setPaymentInstruction(PaymentInstruction::fromArray($data['payment_instruction']));
        }

        $result->setPayer(Payer::fromArray($data['payer']));

        $links = [];
        foreach ($data['links'] as $link) {
            $links[] = Link::fromArray($link);
        }
        $result->setLinks($links);

        if (array_key_exists('redirect_urls', $data)) {
            $result->setRedirectUrls(RedirectUrls::fromArray($data['redirect_urls']));
        }

        if (array_key_exists('credit_financing_offered', $data)) {
            $result->setCreditFinancingOffered(Credit::fromArray($data['credit_financing_offered']));
        }

        return $result;
    }

    public function toArray(): array
    {
        return [
            'intent' => $this->getIntent(),
            'payer' => $this->getPayer()->toArray(),
            'application_context' => $this->getApplicationContext()->toArray(),
            'transactions' => [
                $this->getTransactions()->toArray(),
            ],
            'redirect_urls' => $this->getRedirectUrls()->toArray(),
            'create_time' => $this->getCreateTime(),
            'update_time' => $this->getUpdateTime(),
            'id' => $this->getId(),
            'cart' => $this->getCart(),
            'state' => $this->getState(),
        ];
    }
}
