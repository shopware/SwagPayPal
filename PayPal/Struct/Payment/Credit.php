<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Struct\Payment;

use SwagPayPal\PayPal\Struct\Payment\Credit\Price;

class Credit
{
    /**
     * @var Price
     */
    private $totalCost;

    /**
     * @var int
     */
    private $term;

    /**
     * @var Price
     */
    private $totalInterest;

    /**
     * @var Price
     */
    private $monthlyPayment;

    /**
     * @var bool
     */
    private $payerAcceptance;

    /**
     * @var bool
     */
    private $cartAmountImmutable;

    public function getTotalCost(): Price
    {
        return $this->totalCost;
    }

    public function setTotalCost(Price $totalCost): void
    {
        $this->totalCost = $totalCost;
    }

    public function getTerm(): int
    {
        return $this->term;
    }

    public function setTerm(int $term): void
    {
        $this->term = $term;
    }

    public function getTotalInterest(): Price
    {
        return $this->totalInterest;
    }

    public function setTotalInterest(Price $totalInterest): void
    {
        $this->totalInterest = $totalInterest;
    }

    public function getMonthlyPayment(): Price
    {
        return $this->monthlyPayment;
    }

    public function setMonthlyPayment(Price $monthlyPayment): void
    {
        $this->monthlyPayment = $monthlyPayment;
    }

    public function isPayerAcceptance(): bool
    {
        return $this->payerAcceptance;
    }

    public function setPayerAcceptance(bool $payerAcceptance): void
    {
        $this->payerAcceptance = $payerAcceptance;
    }

    public function isCartAmountImmutable(): bool
    {
        return $this->cartAmountImmutable;
    }

    public function setCartAmountImmutable(bool $cartAmountImmutable): void
    {
        $this->cartAmountImmutable = $cartAmountImmutable;
    }

    public static function fromArray(array $data = null): ?Credit
    {
        if (!$data) {
            return null;
        }

        $result = new self();
        $result->setTotalCost(Price::fromArray($data['total_cost']));
        $result->setTerm($data['term']);
        $result->setMonthlyPayment(Price::fromArray($data['monthly_payment']));
        $result->setTotalInterest(Price::fromArray($data['total_interest']));
        $result->setPayerAcceptance($data['payer_acceptance']);
        $result->setCartAmountImmutable($data['cart_amount_immutable']);

        return $result;
    }
}
