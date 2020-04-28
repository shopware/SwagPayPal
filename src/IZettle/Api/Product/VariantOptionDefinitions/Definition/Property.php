<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Api\Product\VariantOptionDefinitions\Definition;

use Swag\PayPal\IZettle\Api\Common\IZettleStruct;

class Property extends IZettleStruct
{
    /**
     * @var string
     */
    protected $value;

    /**
     * @var string
     */
    protected $imageUrl;

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function setImageUrl(string $imageUrl): void
    {
        $this->imageUrl = $imageUrl;
    }
}
