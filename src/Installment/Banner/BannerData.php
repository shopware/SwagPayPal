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
    /**
     * @var string
     */
    protected $clientId;

    /**
     * @var float
     */
    protected $amount;

    /**
     * @var string
     */
    protected $currency;

    /**
     * @var string
     */
    protected $layout = 'text';

    /**
     * @var string
     */
    protected $color = 'blue';

    /**
     * @var string
     */
    protected $ratio = '8x1';

    /**
     * @var string
     */
    protected $logoType = 'primary';

    /**
     * @var string
     */
    protected $textColor = 'black';

    /**
     * @var string
     */
    private $paymentMethodId;

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
