<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Bancontact;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Blik;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Boletobancario;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Card;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Eps;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Giropay;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Ideal;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Multibanco;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\MyBank;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Oxxo;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\P24;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\PayUponInvoice;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Sofort;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Trustly;

/**
 * @OA\Schema(schema="swag_paypal_v2_order_payment_source")
 */
class PaymentSource extends PayPalApiStruct
{
    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_order_pay_upon_invoice")
     */
    protected ?PayUponInvoice $payUponInvoice = null;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_order_payment_source_bancontact")
     */
    protected ?Bancontact $bancontact = null;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_order_payment_source_blik")
     */
    protected ?Blik $blik = null;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_order_payment_source_boletobancario")
     */
    protected ?Boletobancario $boletobancario = null;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_order_payment_source_card")
     */
    protected ?Card $card = null;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_order_payment_source_eps")
     */
    protected ?Eps $eps = null;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_order_payment_source_giropay")
     */
    protected ?Giropay $giropay = null;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_order_payment_source_ideal")
     */
    protected ?Ideal $ideal = null;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_order_payment_source_multibanco")
     */
    protected ?Multibanco $multibanco = null;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_order_payment_source_mybank")
     */
    protected ?MyBank $myBank = null;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_order_payment_source_oxxo")
     */
    protected ?Oxxo $oxxo = null;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_order_payment_source_p24")
     */
    protected ?P24 $p24 = null;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_order_payment_source_sofort")
     */
    protected ?Sofort $sofort = null;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_order_payment_source_trustly")
     */
    protected ?Trustly $trustly = null;

    public function getPayUponInvoice(): ?PayUponInvoice
    {
        return $this->payUponInvoice;
    }

    public function setPayUponInvoice(?PayUponInvoice $payUponInvoice): void
    {
        $this->payUponInvoice = $payUponInvoice;
    }

    public function getBancontact(): ?Bancontact
    {
        return $this->bancontact;
    }

    public function setBancontact(?Bancontact $bancontact): void
    {
        $this->bancontact = $bancontact;
    }

    public function getBlik(): ?Blik
    {
        return $this->blik;
    }

    public function setBlik(?Blik $blik): void
    {
        $this->blik = $blik;
    }

    public function getBoletobancario(): ?Boletobancario
    {
        return $this->boletobancario;
    }

    public function setBoletobancario(?Boletobancario $boletobancario): void
    {
        $this->boletobancario = $boletobancario;
    }

    public function getCard(): ?Card
    {
        return $this->card;
    }

    public function setCard(?Card $card): void
    {
        $this->card = $card;
    }

    public function getEps(): ?Eps
    {
        return $this->eps;
    }

    public function setEps(?Eps $eps): void
    {
        $this->eps = $eps;
    }

    public function getGiropay(): ?Giropay
    {
        return $this->giropay;
    }

    public function setGiropay(?Giropay $giropay): void
    {
        $this->giropay = $giropay;
    }

    public function getIdeal(): ?Ideal
    {
        return $this->ideal;
    }

    public function setIdeal(?Ideal $ideal): void
    {
        $this->ideal = $ideal;
    }

    public function getMultibanco(): ?Multibanco
    {
        return $this->multibanco;
    }

    public function setMultibanco(?Multibanco $multibanco): void
    {
        $this->multibanco = $multibanco;
    }

    public function getMyBank(): ?MyBank
    {
        return $this->myBank;
    }

    public function setMyBank(?MyBank $myBank): void
    {
        $this->myBank = $myBank;
    }

    public function getOxxo(): ?Oxxo
    {
        return $this->oxxo;
    }

    public function setOxxo(?Oxxo $oxxo): void
    {
        $this->oxxo = $oxxo;
    }

    public function getP24(): ?P24
    {
        return $this->p24;
    }

    public function setP24(?P24 $p24): void
    {
        $this->p24 = $p24;
    }

    public function getSofort(): ?Sofort
    {
        return $this->sofort;
    }

    public function setSofort(?Sofort $sofort): void
    {
        $this->sofort = $sofort;
    }

    public function getTrustly(): ?Trustly
    {
        return $this->trustly;
    }

    public function setTrustly(?Trustly $trustly): void
    {
        $this->trustly = $trustly;
    }

    public function jsonSerialize(): array
    {
        return \array_filter(parent::jsonSerialize());
    }
}
