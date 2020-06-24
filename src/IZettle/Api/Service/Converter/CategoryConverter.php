<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Api\Service\Converter;

use Shopware\Core\Content\Category\CategoryEntity;
use Swag\PayPal\IZettle\Api\Product\Category;

class CategoryConverter
{
    /**
     * @var UuidConverter
     */
    private $uuidConverter;

    public function __construct(UuidConverter $uuidConverter)
    {
        $this->uuidConverter = $uuidConverter;
    }

    public function convert(CategoryEntity $shopwareCategory): Category
    {
        $category = new Category();
        $category->setUuid($this->uuidConverter->convertUuidToV1($shopwareCategory->getId()));
        $category->setName($shopwareCategory->getTranslation('name') ?? $shopwareCategory->getName() ?? '');

        return $category;
    }
}
