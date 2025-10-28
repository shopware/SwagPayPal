<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Struct\V1\Value;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

/**
 * @experimental
 */
#[Package('checkout')]
#[OA\Schema(
    schema: 'paypal_agentic_commerce_v1_value_custom_engraving_text_value',
    required: ['text']
)]
class CustomEngravingTextValue extends Struct
{
    public const FONT__ARIAL = 'arial';
    public const FONT__TIMES = 'times';
    public const FONT__SCRIPT = 'script';
    public const FONT__BLOCK = 'block';

    public const SIZE__SMALL = 'small';
    public const SIZE__MEDIUM = 'medium';
    public const SIZE__LARGE = 'large';

    public const POSITION__FRONT = 'front';
    public const POSITION__BACK = 'back';
    public const POSITION__SIDE = 'side';
    public const POSITION__BOTTOM = 'bottom';

    /**
     * Text to be engraved
     */
    #[OA\Property(
        type: 'string',
        maxLength: 100,
    )]
    protected string $text;

    /**
     * Preferred font style
     */
    #[OA\Property(
        type: 'string',
        enum: [self::FONT__ARIAL, self::FONT__TIMES, self::FONT__SCRIPT, self::FONT__BLOCK],
    )]
    protected ?string $font = null;

    /**
     * Text size preference
     */
    #[OA\Property(
        type: 'string',
        enum: [self::SIZE__SMALL, self::SIZE__MEDIUM, self::SIZE__LARGE]
    )]
    protected ?string $size = null;

    /**
     * Engraving position
     */
    #[OA\Property(
        type: 'string',
        enum: [self::POSITION__FRONT, self::POSITION__BACK, self::POSITION__SIDE, self::POSITION__BOTTOM]
    )]
    protected ?string $position = null;

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): void
    {
        $this->text = $text;
    }

    public function getFont(): ?string
    {
        return $this->font;
    }

    public function setFont(?string $font): void
    {
        if (!\in_array($font, [self::FONT__ARIAL, self::FONT__TIMES, self::FONT__SCRIPT, self::FONT__BLOCK], true)) {
            throw new \InvalidArgumentException('Font must be one of "arial", "times", "script", "block"');
        }

        $this->font = $font;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function setSize(?string $size): void
    {
        if (!\in_array($size, [self::SIZE__SMALL, self::SIZE__MEDIUM, self::SIZE__LARGE], true)) {
            throw new \InvalidArgumentException('Size must be one of "small", "medium", "large"');
        }

        $this->size = $size;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): void
    {
        if (!\in_array($position, [self::POSITION__FRONT, self::POSITION__BACK, self::POSITION__SIDE, self::POSITION__BOTTOM], true)) {
            throw new \InvalidArgumentException('Position must be one of "front", "back", "side", "bottom"');
        }

        $this->position = $position;
    }
}
