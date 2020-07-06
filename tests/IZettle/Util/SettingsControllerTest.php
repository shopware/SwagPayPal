<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Util;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Swag\PayPal\IZettle\Api\Exception\IZettleTokenException;
use Swag\PayPal\IZettle\Resource\TokenResource;
use Swag\PayPal\IZettle\Resource\UserResource;
use Swag\PayPal\IZettle\Setting\Service\ApiCredentialService;
use Swag\PayPal\IZettle\Setting\Service\InformationDefaultService;
use Swag\PayPal\IZettle\Setting\Service\InformationFetchService;
use Swag\PayPal\IZettle\Setting\Service\ProductVisibilityCloneService;
use Swag\PayPal\IZettle\Setting\SettingsController;
use Swag\PayPal\IZettle\Setting\Struct\AdditionalInformation;
use Swag\PayPal\Test\IZettle\ConstantsForTesting;
use Swag\PayPal\Test\IZettle\Mock\Client\IZettleClientFactoryMock;
use Swag\PayPal\Test\IZettle\Mock\Client\TokenClientFactoryMock;
use Swag\PayPal\Test\IZettle\Mock\Repositories\ProductVisibilityRepoMock;
use Swag\PayPal\Test\Mock\CacheMock;
use Symfony\Component\HttpFoundation\Request;

class SettingsControllerTest extends TestCase
{
    use KernelTestBehaviour;

    private const FROM_SALES_CHANNEL = 'salesChannelA';
    private const TO_SALES_CHANNEL = 'salesChannelB';

    /**
     * @var ProductVisibilityRepoMock
     */
    private $productVisibilityRepository;

    public function testValidateCredentialsValid(): void
    {
        $response = $this->getSettingsController()->validateApiCredentials(new Request([], [
            'apiKey' => ConstantsForTesting::VALID_API_KEY,
        ]));
        static::assertEquals($response->getContent(), \json_encode(['credentialsValid' => true]));
    }

    public function testValidateCredentialsInvalid(): void
    {
        $this->expectException(IZettleTokenException::class);
        $this->getSettingsController()->validateApiCredentials(new Request([], [
            'apiKey' => ConstantsForTesting::INVALID_API_KEY,
        ]));
    }

    public function testFetchInformation(): void
    {
        $context = Context::createDefaultContext();
        $response = $this->getSettingsController()->fetchInformation(new Request([], [
            'apiKey' => ConstantsForTesting::VALID_API_KEY,
        ]), $context);
        static::assertEquals($response->getContent(), \json_encode($this->createExpectedFetchedInformation($context)));
    }

    public function testCloneProductVisibility(): void
    {
        $context = Context::createDefaultContext();
        $settingsController = $this->getSettingsController();

        $this->productVisibilityRepository->createMockEntity(self::FROM_SALES_CHANNEL, 30);
        $this->productVisibilityRepository->createMockEntity(self::FROM_SALES_CHANNEL, 30);
        $this->productVisibilityRepository->createMockEntity(self::FROM_SALES_CHANNEL, 30);
        $this->productVisibilityRepository->createMockEntity(self::TO_SALES_CHANNEL, 30);

        static::assertCount(3, $this->productVisibilityRepository->filterBySalesChannelId(self::FROM_SALES_CHANNEL));
        static::assertCount(1, $this->productVisibilityRepository->filterBySalesChannelId(self::TO_SALES_CHANNEL));

        $settingsController->cloneProductVisibility(new Request([], [
            'fromSalesChannelId' => self::FROM_SALES_CHANNEL,
            'toSalesChannelId' => self::TO_SALES_CHANNEL,
        ]), $context);

        static::assertCount(3, $this->productVisibilityRepository->filterBySalesChannelId(self::FROM_SALES_CHANNEL));
        static::assertCount(3, $this->productVisibilityRepository->filterBySalesChannelId(self::TO_SALES_CHANNEL));
    }

    private function getSettingsController(): SettingsController
    {
        /** @var EntityRepositoryInterface $countryRepository */
        $countryRepository = $this->getContainer()->get('country.repository');
        /** @var EntityRepositoryInterface $currencyRepository */
        $currencyRepository = $this->getContainer()->get('currency.repository');
        /** @var EntityRepositoryInterface $languageRepository */
        $languageRepository = $this->getContainer()->get('language.repository');
        /** @var EntityRepositoryInterface $customerGroupRepository */
        $customerGroupRepository = $this->getContainer()->get('customer_group.repository');
        /** @var EntityRepositoryInterface $categoryRepository */
        $categoryRepository = $this->getContainer()->get('category.repository');
        /** @var EntityRepositoryInterface $shippingMethodRepository */
        $shippingMethodRepository = $this->getContainer()->get('shipping_method.repository');
        /** @var EntityRepositoryInterface $paymentMethodRepository */
        $paymentMethodRepository = $this->getContainer()->get('payment_method.repository');
        /** @var EntityRepositoryInterface $deliveryTimeRepository */
        $deliveryTimeRepository = $this->getContainer()->get('delivery_time.repository');
        /** @var EntityRepositoryInterface $ruleRepository */
        $ruleRepository = $this->getContainer()->get('rule.repository');
        /** @var PluginIdProvider $pluginIdProvider */
        $pluginIdProvider = $this->getContainer()->get('Shopware\Core\Framework\Plugin\Util\PluginIdProvider');

        $this->productVisibilityRepository = new ProductVisibilityRepoMock();

        return new SettingsController(
            new ApiCredentialService(new TokenResource(
                new CacheMock(),
                new TokenClientFactoryMock()
            )),
            new InformationFetchService(
                new UserResource(new IZettleClientFactoryMock()),
                $countryRepository,
                $currencyRepository,
                $languageRepository
            ),
            new InformationDefaultService(
                $customerGroupRepository,
                $categoryRepository,
                $pluginIdProvider,
                $paymentMethodRepository,
                $ruleRepository,
                $deliveryTimeRepository,
                $shippingMethodRepository
            ),
            new ProductVisibilityCloneService(
                $this->productVisibilityRepository
            )
        );
    }

    private function createExpectedFetchedInformation(Context $context): AdditionalInformation
    {
        $countryCriteria = new Criteria();
        $countryCriteria->addFilter(new EqualsFilter('iso', 'DE'));
        $countryCriteria->setLimit(1);
        /** @var EntityRepositoryInterface $countryRepository */
        $countryRepository = $this->getContainer()->get('country.repository');

        $languageCriteria = new Criteria();
        $languageCriteria->addFilter(new EqualsFilter('name', 'Deutsch'));
        $languageCriteria->setLimit(1);
        /** @var EntityRepositoryInterface $languageRepository */
        $languageRepository = $this->getContainer()->get('language.repository');

        $customerGroupCriteria = new Criteria();
        $customerGroupCriteria->addFilter(new EqualsFilter('displayGross', true));
        $customerGroupCriteria->addSorting(new FieldSorting('createdAt'));
        $customerGroupCriteria->setLimit(1);
        /** @var EntityRepositoryInterface $customerGroupRepository */
        $customerGroupRepository = $this->getContainer()->get('customer_group.repository');

        $categoryCriteria = new Criteria();
        $categoryCriteria->addFilter(new EqualsFilter('parentId', null));
        $categoryCriteria->addSorting(new FieldSorting('createdAt'));
        $categoryCriteria->setLimit(1);
        /** @var EntityRepositoryInterface $categoryRepository */
        $categoryRepository = $this->getContainer()->get('category.repository');

        $shippingMethodCriteria = new Criteria([InformationDefaultService::IZETTLE_SHIPPING_METHOD_ID]);
        /** @var EntityRepositoryInterface $shippingMethodRepository */
        $shippingMethodRepository = $this->getContainer()->get('shipping_method.repository');

        $paymentMethodCriteria = new Criteria([InformationDefaultService::IZETTLE_PAYMENT_METHOD_ID]);
        /** @var EntityRepositoryInterface $paymentMethodRepository */
        $paymentMethodRepository = $this->getContainer()->get('payment_method.repository');

        $expected = new AdditionalInformation();
        $expected->assign([
            'countryId' => $countryRepository->searchIds($countryCriteria, $context)->firstId(),
            'currencyId' => Defaults::CURRENCY,
            'languageId' => $languageRepository->searchIds($languageCriteria, $context)->firstId(),
            'customerGroupId' => $customerGroupRepository->searchIds($customerGroupCriteria, $context)->firstId(),
            'navigationCategoryId' => $categoryRepository->searchIds($categoryCriteria, $context)->firstId(),
            'shippingMethodId' => $shippingMethodRepository->searchIds($shippingMethodCriteria, $context)->firstId(),
            'paymentMethodId' => $paymentMethodRepository->searchIds($paymentMethodCriteria, $context)->firstId(),
        ]);

        return $expected;
    }
}
