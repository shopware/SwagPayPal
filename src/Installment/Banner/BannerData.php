<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Installment\Banner;

use Shopware\Core\Framework\Struct\Struct;

class BannerData extends Struct
{
    protected string $clientId;

    protected float $amount;

    protected string $currency;

    protected string $layout = 'text';

    protected string $color = 'blue';

    protected string $ratio = '8x1';

    protected string $logoType = 'primary';

    protected string $textColor = 'black';

    private string $paymentMethodId;

    public function __construct(
        string $paymentMethodId,
        string $clientId,
        float $amount,
        string $currency,
        string $layout = 'text',
        string $color = 'blue',
        string $ratio = '8x1',
        string $logoType = 'primary',
        string $textColor = 'black'
    ) {
        $this->paymentMethodId = $paymentMethodId;
        $this->clientId = $clientId;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->layout = $layout;
        $this->color = $color;
        $this->ratio = $ratio;
        $this->logoType = $logoType;
        $this->textColor = $textColor;
    }

    public function getPaymentMethodId(): string
    {
        return $this->paymentMethodId;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getLayout(): string
    {
        return $this->layout;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function getRatio(): string
    {
        return $this->ratio;
    }

    public function getLogoType(): string
    {
        return $this->logoType;
    }

    public function getTextColor(): string
    {
        return $this->textColor;
    }
}
