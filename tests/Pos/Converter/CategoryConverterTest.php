<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Swag\PayPal\Pos\Api\Service\Converter\CategoryConverter;
use Swag\PayPal\Pos\Api\Service\Converter\UuidConverter;

class CategoryConverterTest extends TestCase
{
    use KernelTestBehaviour;

    public function testConvert(): void
    {
        $shopwareCategory = $this->getCategory();
        if ($shopwareCategory === null) {
            return;
        }
        $category = $this->createCategoryConverter()->convert($shopwareCategory);
        static::assertEquals($shopwareCategory->getTranslation('name'), $category->getName());
        static::assertEquals((new UuidConverter())->convertUuidToV1($shopwareCategory->getId()), $category->getUuid());
    }

    private function createCategoryConverter(): CategoryConverter
    {
        return new CategoryConverter(new UuidConverter());
    }

    private function getCategory(): ?CategoryEntity
    {
        $criteria = new Criteria();
        $criteria->addAssociation('translation');

        /** @var EntityRepositoryInterface $categoryRepository */
        $categoryRepository = $this->getContainer()->get('category.repository');

        return $categoryRepository->search($criteria, Context::createDefaultContext())->first();
    }
}
