<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Component\Patch;

use SwagPayPal\PayPal\Struct\Payment\Transactions\Amount;

class PaymentAmountPatch implements PatchInterface
{
    public const PATH = '/transactions/0/amount';

    /**
     * @var Amount
     */
    private $amount;

    /**
     * @param Amount $amount
     */
    public function __construct(Amount $amount)
    {
        $this->amount = $amount;
    }

    /**
     * {@inheritdoc}
     */
    public function getOperation(): string
    {
        return self::OPERATION_REPLACE;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath(): string
    {
        return self::PATH;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue(): array
    {
        return $this->amount->toArray();
    }
}
