<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SPBCheckout;

use Shopware\Core\Framework\Struct\Struct;

class SPBCheckoutButtonData extends Struct
{
    /**
     * @var string
     */
    protected $clientId;

    /**
     * @var string
     */
    protected $languageIso;

    /**
     * @var string
     */
    protected $currency;

    /**
     * @var string
     */
    protected $intent;

    /**
     * @var string
     */
    protected $paymentMethodId;

    /**
     * @var bool
     */
    protected $useAlternativePaymentMethods;

    /**
     * @var string
     */
    protected $createPaymentUrl;

    /**
     * @var string
     */
    protected $checkoutConfirmUrl;

    public function getClientId(): string
    {
        return $this->clientId;
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

    public function getPaymentMethodId(): string
    {
        return $this->paymentMethodId;
    }

    public function getUseAlternativePaymentMethods(): bool
    {
        return $this->useAlternativePaymentMethods;
    }

    public function getCreatePaymentUrl(): string
    {
        return $this->createPaymentUrl;
    }

    public function getCheckoutConfirmUrl(): string
    {
        return $this->checkoutConfirmUrl;
    }
}
