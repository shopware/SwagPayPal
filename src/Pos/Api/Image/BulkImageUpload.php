<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Image;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Common\PosStruct;
use Swag\PayPal\Pos\Api\Image\BulkImageUpload\ImageUpload;

#[Package('checkout')]
class BulkImageUpload extends PosStruct
{
    /**
     * @var ImageUpload[]
     */
    protected array $imageUploads = [];

    /**
     * @return ImageUpload[]
     */
    public function getImageUploads(): array
    {
        return $this->imageUploads;
    }

    public function addImageUpload(ImageUpload ...$imageUploads): void
    {
        foreach ($imageUploads as $imageUpload) {
            $this->imageUploads[] = $imageUpload;
        }
    }

    public function setImageUploads(array $imageUploads): void
    {
        $this->imageUploads = $imageUploads;
    }
}
