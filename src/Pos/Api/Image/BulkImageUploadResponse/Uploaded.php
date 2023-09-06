<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Image\BulkImageUploadResponse;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Common\PosStruct;

#[Package('checkout')]
class Uploaded extends PosStruct
{
    protected string $imageLookupKey;

    /**
     * @var string[]
     */
    protected array $imageUrls;

    protected string $source;

    public function getImageLookupKey(): string
    {
        return $this->imageLookupKey;
    }

    public function setImageLookupKey(string $imageLookupKey): void
    {
        $this->imageLookupKey = $imageLookupKey;
    }

    /**
     * @return string[]
     */
    public function getImageUrls(): array
    {
        return $this->imageUrls;
    }

    /**
     * @param string[] $imageUrls
     */
    public function setImageUrls(array $imageUrls): void
    {
        $this->imageUrls = $imageUrls;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): void
    {
        $this->source = $source;
    }
}
