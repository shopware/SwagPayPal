<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Installment\Banner;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Storefront\Data\Struct\AbstractScriptData;

#[Package('checkout')]
class BannerData extends AbstractScriptData
{
    public const TEXT_COLOR_BLACK = 'BLACK';
    public const TEXT_COLOR_WHITE = 'WHITE';
    public const TEXT_COLOR_MONOCHROME  = 'MONOCHROME';

    public const LOGO_POSITION_INLINE = 'INLINE';
    public const LOGO_POSITION_LEFT = 'LEFT';
    public const LOGO_POSITION_RIGHT = 'RIGHT';
    public const LOGO_POSITION_TOP = 'TOP';

    public const LOGO_TYPE_TEXT = 'TEXT';
    public const LOGO_TYPE_MONOGRAM = 'MONOGRAM';
    public const LOGO_TYPE_WORDMARK = 'WORDMARK';

    protected float $amount;

    protected string $layout = 'text';

    protected string $color = 'blue';

    protected string $ratio = '8x1';

    protected string $logoType = self::LOGO_TYPE_WORDMARK;

    protected string $logoPosition = self::LOGO_POSITION_LEFT;

    protected string $textColor = self::TEXT_COLOR_BLACK;

    protected string $paymentMethodId;

    protected bool $footerEnabled;

    protected bool $cartEnabled;

    protected bool $offCanvasCartEnabled;

    protected bool $loginPageEnabled;

    protected bool $detailPageEnabled;

    protected ?string $crossBorderBuyerCountry;

    public function getPaymentMethodId(): string
    {
        return $this->paymentMethodId;
    }

    public function setPaymentMethodId(string $paymentMethodId): void
    {
        $this->paymentMethodId = $paymentMethodId;
    }

    public function getAmount(): float
    {
        return $this->amount;
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
        if (!$this->isV6Enabled()) {
            return $this->logoType;
        }

        return match ($this->logoType) {
            'primary' => ['logoType' => self::LOGO_TYPE_WORDMARK, 'logoPosition' => self::LOGO_POSITION_LEFT],
            'alternative' => ['logoType' => self::LOGO_TYPE_MONOGRAM, 'logoPosition' => self::LOGO_POSITION_LEFT],
            'inline' => ['logoType' => self::LOGO_TYPE_WORDMARK, 'logoPosition' => self::LOGO_POSITION_INLINE],
            'none' => ['logoType' => self::LOGO_TYPE_TEXT, 'logoPosition' => self::LOGO_POSITION_INLINE],
            default => $this->logoType,
        };
    }

    public function getTextColor(): string
    {
        if (!$this->isV6Enabled()) {
            return $this->textColor;
        }

        return match ($this->textColor) {
            'grayscale' => self::TEXT_COLOR_MONOCHROME,
            default => $this->textColor,
        };
    }

    public function getFooterEnabled(): bool
    {
        return $this->footerEnabled;
    }

    public function getCartEnabled(): bool
    {
        return $this->cartEnabled;
    }

    public function getOffCanvasCartEnabled(): bool
    {
        return $this->offCanvasCartEnabled;
    }

    public function getLoginPageEnabled(): bool
    {
        return $this->loginPageEnabled;
    }

    public function getDetailPageEnabled(): bool
    {
        return $this->detailPageEnabled;
    }

    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    public function setLayout(string $layout): void
    {
        $this->layout = $layout;
    }

    public function setColor(string $color): void
    {
        $this->color = $color;
    }

    public function setRatio(string $ratio): void
    {
        $this->ratio = $ratio;
    }

    public function setLogoType(string $logoType): void
    {
        $this->logoType = $logoType;
    }

    public function setTextColor(string $textColor): void
    {
        $this->textColor = $textColor;
    }

    public function setFooterEnabled(bool $footerEnabled): void
    {
        $this->footerEnabled = $footerEnabled;
    }

    public function setCartEnabled(bool $cartEnabled): void
    {
        $this->cartEnabled = $cartEnabled;
    }

    public function setOffCanvasCartEnabled(bool $offCanvasCartEnabled): void
    {
        $this->offCanvasCartEnabled = $offCanvasCartEnabled;
    }

    public function setLoginPageEnabled(bool $loginPageEnabled): void
    {
        $this->loginPageEnabled = $loginPageEnabled;
    }

    public function setDetailPageEnabled(bool $detailPageEnabled): void
    {
        $this->detailPageEnabled = $detailPageEnabled;
    }

    public function getCrossBorderBuyerCountry(): ?string
    {
        return $this->crossBorderBuyerCountry;
    }

    public function setCrossBorderBuyerCountry(?string $crossBorderBuyerCountry): void
    {
        $this->crossBorderBuyerCountry = $crossBorderBuyerCountry;
    }

    public function getLogoPosition(): string
    {
        return match ($this->logoType) {
            self::LOGO_TYPE_MONOGRAM => self::LOGO_POSITION_LEFT,
            self::LOGO_TYPE_TEXT => self::LOGO_POSITION_INLINE,
            default => $this->logoPosition,
        };
    }

    public function setLogoPosition(string $logoPosition): void
    {
        $this->logoPosition = $logoPosition;
    }

    public function jsonSerialize(): array
    {
        return [
            ...parent::jsonSerialize(),
            'logoPosition' => $this->getLogoPosition(),
            'logoType' => $this->getLogoType(),
            'textColor' => $this->getTextColor(),
        ];
    }
}
