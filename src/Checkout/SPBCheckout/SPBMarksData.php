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

    protected string $languageIso;

    protected string $currency;

    protected string $intent;

    /**
     * @deprecated tag:v4.0.0 - parametrized constructor will be removed. Use assign() instead
     */
    public function __construct(string $clientId = '', string $paymentMethodId = '', bool $useAlternativePaymentMethods = true)
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

    public function getUseAlternativePaymentMethods(): bool
    {
        return $this->useAlternativePaymentMethods;
    }

    public function getLanguageIso(): string
    {
        return $this->languageIso;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getIntent(): string
    {
        return $this->intent;
    }
}
