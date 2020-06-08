<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Api\Image\BulkImageUploadResponse;

use Swag\PayPal\IZettle\Api\Common\IZettleStruct;

class Uploaded extends IZettleStruct
{
    /**
     * @var string
     */
    protected $imageLookupKey;

    /**
     * @var string[]
     */
    protected $imageUrls;

    /**
     * @var string
     */
    protected $source;

    public function getImageLookupKey(): string
    {
        return $this->imageLookupKey;
    }

    /**
     * @return string[]
     */
    public function getImageUrls(): array
    {
        return $this->imageUrls;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    protected function setImageLookupKey(string $imageLookupKey): void
    {
        $this->imageLookupKey = $imageLookupKey;
    }

    /**
     * @param string[] $imageUrls
     */
    protected function setImageUrls(array $imageUrls): void
    {
        $this->imageUrls = $imageUrls;
    }

    protected function setSource(string $source): void
    {
        $this->source = $source;
    }
}
