<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Component\Patch;

use SwagPayPal\PayPal\Struct\Payment\Payer\PayerInfo;

class PayerInfoPatch implements PatchInterface
{
    public const PATH = '/payer/payer_info';

    /**
     * @var PayerInfo
     */
    private $payerInfo;

    /**
     * @param PayerInfo $payerInfo
     */
    public function __construct(PayerInfo $payerInfo)
    {
        $this->payerInfo = $payerInfo;
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
        return $this->payerInfo->toArray();
    }
}
