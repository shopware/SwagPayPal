<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Component\Patch;

class PaymentOrderNumberPatch implements PatchInterface
{
    public const PATH = '/transactions/0/invoice_number';

    /**
     * @var string
     */
    private $orderNumber;

    /**
     * @param string $orderNumber
     */
    public function __construct($orderNumber)
    {
        $this->orderNumber = $orderNumber;
    }

    /**
     * {@inheritdoc}
     */
    public function getOperation(): string
    {
        return self::OPERATION_ADD;
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
        return [$this->orderNumber];
    }
}
