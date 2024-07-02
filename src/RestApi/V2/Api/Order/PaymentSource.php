<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\AbstractPaymentSource;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\ApplePay;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Bancontact;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Blik;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Boletobancario;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Card;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Eps;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Giropay;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\GooglePay;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Ideal;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Multibanco;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\MyBank;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Oxxo;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\P24;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Paypal;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\PayUponInvoice;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Sofort;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Token;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Trustly;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Venmo;

#[OA\Schema(schema: 'swag_paypal_v2_order_payment_source')]
#[Package('checkout')]
class PaymentSource extends PayPalApiStruct
{
    #[OA\Property(ref: ApplePay::class)]
    protected ?ApplePay $applePay = null;

    #[OA\Property(ref: PayUponInvoice::class, nullable: true)]
    protected ?PayUponInvoice $payUponInvoice = null;

    #[OA\Property(ref: Bancontact::class, nullable: true)]
    protected ?Bancontact $bancontact = null;

    #[OA\Property(ref: Blik::class, nullable: true)]
    protected ?Blik $blik = null;

    #[OA\Property(ref: Boletobancario::class, nullable: true)]
    protected ?Boletobancario $boletobancario = null;

    #[OA\Property(ref: Card::class, nullable: true)]
    protected ?Card $card = null;

    #[OA\Property(ref: Eps::class, nullable: true)]
    protected ?Eps $eps = null;

    /**
     * @deprecated tag:v10.0.0 - will be removed, payment method has been disabled
     */
    #[OA\Property(ref: Giropay::class, nullable: true)]
    protected ?Giropay $giropay = null;

    #[OA\Property(ref: Ideal::class, nullable: true)]
    protected ?Ideal $ideal = null;

    #[OA\Property(ref: Multibanco::class, nullable: true)]
    protected ?Multibanco $multibanco = null;

    #[OA\Property(ref: MyBank::class, nullable: true)]
    protected ?MyBank $myBank = null;

    #[OA\Property(ref: Oxxo::class, nullable: true)]
    protected ?Oxxo $oxxo = null;

    #[OA\Property(ref: P24::class, nullable: true)]
    protected ?P24 $p24 = null;

    #[OA\Property(ref: Paypal::class, nullable: true)]
    protected ?Paypal $paypal = null;

    /**
     * @deprecated tag:v10.0.0 - will be removed, payment method has been disabled
     */
    #[OA\Property(ref: Sofort::class, nullable: true)]
    protected ?Sofort $sofort = null;

    #[OA\Property(ref: Token::class, nullable: true)]
    protected ?Token $token = null;

    #[OA\Property(ref: Trustly::class, nullable: true)]
    protected ?Trustly $trustly = null;

    #[OA\Property(ref: GooglePay::class, nullable: true)]
    protected ?GooglePay $googlePay = null;

    #[OA\Property(ref: Venmo::class, nullable: true)]
    protected ?Venmo $venmo = null;

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

    /**
     * @deprecated tag:v10.0.0 - will be removed, payment method has been disabled
     */
    public function getGiropay(): ?Giropay
    {
        return $this->giropay;
    }

    /**
     * @deprecated tag:v10.0.0 - will be removed, payment method has been disabled
     */
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

    public function getPaypal(): ?Paypal
    {
        return $this->paypal;
    }

    public function setPaypal(?Paypal $paypal): void
    {
        $this->paypal = $paypal;
    }

    /**
     * @deprecated tag:v10.0.0 - will be removed, payment method has been disabled
     */
    public function getSofort(): ?Sofort
    {
        return $this->sofort;
    }

    /**
     * @deprecated tag:v10.0.0 - will be removed, payment method has been disabled
     */
    public function setSofort(?Sofort $sofort): void
    {
        $this->sofort = $sofort;
    }

    public function getToken(): ?Token
    {
        return $this->token;
    }

    public function setToken(?Token $token): void
    {
        $this->token = $token;
    }

    public function getTrustly(): ?Trustly
    {
        return $this->trustly;
    }

    public function setTrustly(?Trustly $trustly): void
    {
        $this->trustly = $trustly;
    }

    public function getGooglePay(): ?GooglePay
    {
        return $this->googlePay;
    }

    public function setGooglePay(?GooglePay $googlePay): void
    {
        $this->googlePay = $googlePay;
    }

    public function getVenmo(): ?Venmo
    {
        return $this->venmo;
    }

    public function setVenmo(?Venmo $venmo): void
    {
        $this->venmo = $venmo;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return \array_filter(parent::jsonSerialize());
    }

    /**
     * @template T
     *
     * @param class-string<T> $expectedType
     *
     * @return (T&AbstractPaymentSource)|null
     */
    public function first(string $expectedType = AbstractPaymentSource::class): ?AbstractPaymentSource
    {
        foreach ($this->jsonSerialize() as $paymentSource) {
            if ($paymentSource instanceof $expectedType && $paymentSource instanceof AbstractPaymentSource) {
                return $paymentSource;
            }
        }

        return null;
    }

    public function getApplePay(): ?ApplePay
    {
        return $this->applePay;
    }

    public function setApplePay(?ApplePay $applePay): void
    {
        $this->applePay = $applePay;
    }
}
