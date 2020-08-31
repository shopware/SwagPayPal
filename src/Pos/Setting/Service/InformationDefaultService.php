<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Setting\Service;

use Shopware\Core\Content\Category\Exception\CategoryNotFoundException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\System\DeliveryTime\DeliveryTimeEntity;
use Swag\PayPal\Pos\Payment\PosPayment;
use Swag\PayPal\Pos\Setting\Exception\CustomerGroupNotFoundException;
use Swag\PayPal\Pos\Setting\Struct\AdditionalInformation;
use Swag\PayPal\SwagPayPal;

class InformationDefaultService
{
    public const POS_PAYMENT_METHOD_ID = 'abab06a108014a37b5f49c9a4d8943db';
    public const POS_SHIPPING_METHOD_ID = '405481da0a20443e94ce45f52b1af776';

    /**
     * @var EntityRepositoryInterface
     */
    private $customerGroupRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var PluginIdProvider
     */
    private $pluginIdProvider;

    /**
     * @var EntityRepositoryInterface
     */
    private $paymentMethodRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $ruleRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $shippingMethodRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $deliveryTimeRepository;

    public function __construct(
        EntityRepositoryInterface $customerGroupRepository,
        EntityRepositoryInterface $categoryRepository,
        PluginIdProvider $pluginIdProvider,
        EntityRepositoryInterface $paymentMethodRepository,
        EntityRepositoryInterface $ruleRepository,
        EntityRepositoryInterface $deliveryTimeRepository,
        EntityRepositoryInterface $shippingMethodRepository
    ) {
        $this->customerGroupRepository = $customerGroupRepository;
        $this->categoryRepository = $categoryRepository;
        $this->pluginIdProvider = $pluginIdProvider;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->ruleRepository = $ruleRepository;
        $this->deliveryTimeRepository = $deliveryTimeRepository;
        $this->shippingMethodRepository = $shippingMethodRepository;
    }

    public function addInformation(
        AdditionalInformation $information,
        Context $context
    ): void {
        $information->setCustomerGroupId($this->getCustomerGroupId($context));
        $information->setNavigationCategoryId($this->getNavigationCategoryId($context));
        $information->setPaymentMethodId($this->getPaymentMethodId($context));
        $information->setShippingMethodId($this->getShippingMethodId($context));
    }

    private function getCustomerGroupId(Context $context): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('displayGross', true));
        $criteria->addSorting(new FieldSorting('createdAt'));
        $criteria->setLimit(1);

        $firstId = $this->customerGroupRepository->searchIds($criteria, $context)->firstId();
        if ($firstId === null) {
            throw new CustomerGroupNotFoundException();
        }

        return $firstId;
    }

    private function getNavigationCategoryId(Context $context): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('parentId', null));
        $criteria->addSorting(new FieldSorting('createdAt'));
        $criteria->setLimit(1);

        $firstId = $this->categoryRepository->searchIds($criteria, $context)->firstId();
        if ($firstId === null) {
            throw new CategoryNotFoundException('root');
        }

        return $firstId;
    }

    private function getPaymentMethodId(Context $context): string
    {
        $criteria = new Criteria([self::POS_PAYMENT_METHOD_ID]);
        $criteria->addFilter(new EqualsFilter('handlerIdentifier', PosPayment::class));
        $firstId = $this->paymentMethodRepository->searchIds($criteria, $context)->firstId();
        if ($firstId !== null) {
            return $firstId;
        }

        $pluginId = $this->pluginIdProvider->getPluginIdByBaseClass(SwagPayPal::class, $context);
        $this->paymentMethodRepository->upsert([[
            'id' => self::POS_PAYMENT_METHOD_ID,
            'handlerIdentifier' => PosPayment::class,
            'active' => false,
            'position' => 999,
            'name' => 'iZettle',
            'pluginId' => $pluginId,
            'description' => 'Payment via iZettle. Do not activate or use.',
            'translations' => [
                'de-DE' => [
                    'description' => 'Bezahlung per iZettle. Nicht aktivieren oder nutzen.',
                ],
                'en-GB' => [
                    'description' => 'Payment via iZettle. Do not activate or use.',
                ],
            ],
        ]], $context);

        return self::POS_PAYMENT_METHOD_ID;
    }

    private function getShippingMethodId(Context $context): string
    {
        $criteria = new Criteria([self::POS_SHIPPING_METHOD_ID]);
        $firstId = $this->shippingMethodRepository->searchIds($criteria, $context)->firstId();

        if ($firstId !== null) {
            return $firstId;
        }

        $this->shippingMethodRepository->create([[
            'id' => self::POS_SHIPPING_METHOD_ID,
            'active' => false,
            'availabilityRuleId' => $this->getAvailibityRuleId($context),
            'deliveryTimeId' => $this->getDeliveryTimeId($context),
            'name' => 'iZettle',
            'description' => 'Shipping via iZettle. Do not activate or use.',
            'translations' => [
                'de-DE' => [
                    'description' => 'Versand per iZettle. Nicht aktivieren oder nutzen.',
                ],
                'en-GB' => [
                    'description' => 'Shipping via iZettle. Do not activate or use.',
                ],
            ],
        ]], $context);

        return self::POS_SHIPPING_METHOD_ID;
    }

    private function getAvailibityRuleId(Context $context): ?string
    {
        $ruleCriteria = new Criteria();
        $ruleCriteria->addFilter(new EqualsFilter('name', 'Cart >= 0'));

        return $this->ruleRepository->searchIds($ruleCriteria, $context)->firstId();
    }

    private function getDeliveryTimeId(Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('min', 0));
        $criteria->addFilter(new EqualsFilter('max', 0));
        $criteria->addFilter(new EqualsFilter('unit', DeliveryTimeEntity::DELIVERY_TIME_DAY));
        $firstId = $this->deliveryTimeRepository->searchIds($criteria, $context)->firstId();

        if ($firstId !== null) {
            return $firstId;
        }

        $this->deliveryTimeRepository->create([[
            'min' => 0,
            'max' => 0,
            'unit' => DeliveryTimeEntity::DELIVERY_TIME_DAY,
            'name' => 'Immediately',
            'translations' => [
                'de-DE' => [
                    'name' => 'Sofort',
                ],
                'en-GB' => [
                    'name' => 'Immediately',
                ],
            ],
        ]], $context);

        $firstId = $this->deliveryTimeRepository->searchIds($criteria, $context)->firstId();

        return $firstId;
    }
}
