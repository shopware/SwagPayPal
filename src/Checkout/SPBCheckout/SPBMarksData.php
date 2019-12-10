<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SPBCheckout;

use Shopware\Core\Framework\Struct\Struct;

class SPBMarksData extends Struct
{
    /**
     * @var string
     */
    protected $clientId;

    /**
     * @var string
     */
    protected $paymentMethodId;

    /**
     * @var bool
     */
    protected $useAlternativePaymentMethods;

    public function __construct(string $clientId, string $paymentMethodId, bool $useAlternativePaymentMethods)
    {
        $this->clientId = $clientId;
        $this->paymentMethodId = $paymentMethodId;
        $this->useAlternativePaymentMethods = $useAlternativePaymentMethods;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getPaymentMethodId(): string
    {
        return $this->paymentMethodId;
    }

    public function isUseAlternativePaymentMethods(): bool
    {
        return $this->useAlternativePaymentMethods;
    }
}
