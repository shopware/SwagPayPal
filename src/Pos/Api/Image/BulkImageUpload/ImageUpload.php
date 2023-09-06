<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Image\BulkImageUpload;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Common\PosStruct;

#[Package('checkout')]
class ImageUpload extends PosStruct
{
    protected string $imageFormat;

    protected string $imageUrl;

    protected ?string $imageLookupKey = null;

    public function getImageFormat(): string
    {
        return $this->imageFormat;
    }

    public function setImageFormat(string $imageFormat): void
    {
        $this->imageFormat = $imageFormat;
    }

    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(string $imageUrl): void
    {
        $this->imageUrl = $imageUrl;
    }

    public function getImageLookupKey(): ?string
    {
        return $this->imageLookupKey;
    }

    public function setImageLookupKey(?string $imageLookupKey): void
    {
        $this->imageLookupKey = $imageLookupKey;
    }
}
