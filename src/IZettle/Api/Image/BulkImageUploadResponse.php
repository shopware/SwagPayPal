<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Api\Image;

use Swag\PayPal\IZettle\Api\Common\IZettleStruct;
use Swag\PayPal\IZettle\Api\Image\BulkImageUploadResponse\Uploaded;

class BulkImageUploadResponse extends IZettleStruct
{
    /**
     * @var Uploaded[]
     */
    protected $uploaded;

    /**
     * @var string[]
     */
    protected $invalid;

    /**
     * @return Uploaded[]
     */
    public function getUploaded(): array
    {
        return $this->uploaded;
    }

    /**
     * @return string[]
     */
    public function getInvalid(): array
    {
        return $this->invalid;
    }

    /**
     * @param Uploaded[] $uploaded
     */
    protected function setUploaded(array $uploaded): void
    {
        $this->uploaded = $uploaded;
    }

    /**
     * @param string[] $invalid
     */
    protected function setInvalid(array $invalid): void
    {
        $this->invalid = $invalid;
    }
}
