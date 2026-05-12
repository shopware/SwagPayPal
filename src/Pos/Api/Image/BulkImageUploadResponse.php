<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Image;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Common\PosStruct;
use Swag\PayPal\Pos\Api\Image\BulkImageUploadResponse\Uploaded;

#[Package('checkout')]
class BulkImageUploadResponse extends PosStruct
{
    /**
     * @var Uploaded[]
     */
    protected array $uploaded;

    /**
     * @var string[]
     */
    protected array $invalid;

    /**
     * @return Uploaded[]
     */
    public function getUploaded(): array
    {
        return $this->uploaded;
    }

    /**
     * @param Uploaded[] $uploaded
     */
    public function setUploaded(array $uploaded): void
    {
        $this->uploaded = $uploaded;
    }

    /**
     * @return string[]
     */
    public function getInvalid(): array
    {
        return $this->invalid;
    }

    /**
     * @param string[] $invalid
     */
    public function setInvalid(array $invalid): void
    {
        $this->invalid = $invalid;
    }
}
