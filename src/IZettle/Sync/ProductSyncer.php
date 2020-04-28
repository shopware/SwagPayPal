<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Sync;

use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\ProductStream\Service\ProductStreamBuilderInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceInterface;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\IZettle\Api\Error;
use Swag\PayPal\IZettle\Api\Exception\IZettleApiException;
use Swag\PayPal\IZettle\Api\Service\ProductConverter;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelEntity;
use Swag\PayPal\IZettle\Resource\ProductResource;

class ProductSyncer
{
    /**
     * @var SalesChannelRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ProductResource
     */
    private $productResource;

    /**
     * @var ProductConverter
     */
    private $productConverter;

    /**
     * @var ProductStreamBuilderInterface
     */
    private $productStreamBuilder;

    /**
     * @var EntityRepositoryInterface
     */
    private $domainRepository;

    /**
     * @var SalesChannelContextServiceInterface
     */
    private $salesChannelContextService;

    /**
     * @var ChecksumResource
     */
    private $checksumResource;

    public function __construct(
        SalesChannelRepositoryInterface $productRepository,
        ProductResource $productResource,
        ProductConverter $productConverter,
        ProductStreamBuilderInterface $productStreamBuilder,
        EntityRepositoryInterface $domainRepository,
        SalesChannelContextServiceInterface $salesChannelContextService,
        ChecksumResource $checksumResource
    ) {
        $this->productRepository = $productRepository;
        $this->productResource = $productResource;
        $this->productConverter = $productConverter;
        $this->productStreamBuilder = $productStreamBuilder;
        $this->domainRepository = $domainRepository;
        $this->salesChannelContextService = $salesChannelContextService;
        $this->checksumResource = $checksumResource;
    }

    public function syncProducts(SalesChannelEntity $salesChannel, Context $context): void
    {
        /** @var IZettleSalesChannelEntity $iZettleSalesChannel */
        $iZettleSalesChannel = $salesChannel->getExtension('paypalIZettleSalesChannel');

        $salesChannelContext = $this->getSalesChannelContext($iZettleSalesChannel, $context);

        $productStreamId = $iZettleSalesChannel->getProductStreamId();
        if ($productStreamId) {
            $criteria = $this->getProductStreamCriteria($productStreamId, $context);
        } else {
            $criteria = new Criteria();
        }

        $currency = $iZettleSalesChannel->isSyncPrices() ? $salesChannel->getCurrency() : null;

        /** @var ProductCollection $shopwareProducts */
        $shopwareProducts = $this->productRepository->search($criteria, $salesChannelContext)->getEntities();

        $this->checksumResource->begin($salesChannel->getId(), $context);

        $productGroupings = $this->productConverter->convertShopwareProducts($shopwareProducts, $currency);

        foreach ($productGroupings as $productGrouping) {
            $product = $productGrouping->getProduct();
            $shopwareProduct = $productGrouping->getIdentifyingEntity();

            $updateStatus = $this->checksumResource->checkForUpdate($shopwareProduct, $product);

            if ($updateStatus === ChecksumResource::PRODUCT_NEW) {
                try {
                    $this->productResource->createProduct($iZettleSalesChannel, $product);
                    $this->checksumResource->addProduct($shopwareProduct, $product, $salesChannel->getId());
                } catch (IZettleApiException $iZettleApiException) {
                    if ($iZettleApiException->getApiError()->getErrorType() === Error::ERROR_TYPE_ITEM_ALREADY_EXISTS) {
                        $updateStatus = ChecksumResource::PRODUCT_OUTDATED;
                    } else {
                        throw $iZettleApiException;
                    }
                }
            }

            if ($updateStatus === ChecksumResource::PRODUCT_OUTDATED) {
                try {
                    $this->productResource->updateProduct($iZettleSalesChannel, $product);
                    $this->checksumResource->addProduct($shopwareProduct, $product, $salesChannel->getId());
                } catch (IZettleApiException $iZettleApiException) {
                    if ($iZettleApiException->getApiError()->getErrorType() === Error::ERROR_TYPE_ENTITY_NOT_FOUND) {
                        $this->checksumResource->removeProduct($shopwareProduct, $salesChannel->getId());
                    } else {
                        throw $iZettleApiException;
                    }
                }
            }
        }

        $this->checksumResource->commit($context);
    }

    private function getProductStreamCriteria(string $productStreamId, Context $context): Criteria
    {
        $filters = $this->productStreamBuilder->buildFilters(
            $productStreamId,
            $context
        );

        $criteria = new Criteria();
        $criteria
            ->addFilter(...$filters)
            ->addAssociation('categories')
            ->addAssociation('cover')
            ->addAssociation('prices')
            ->addAssociation('translation')
            ->addAssociation('configuratorSettings.option.translation')
            ->addAssociation('configuratorSettings.option.group.translation')
            ->addAssociation('options.translation')
            ->addAssociation('options.group.translation');

        return $criteria;
    }

    private function getSalesChannelContext(IZettleSalesChannelEntity $iZettleSalesChannel, Context $context): SalesChannelContext
    {
        $criteria = new Criteria();
        $criteria->setIds([$iZettleSalesChannel->getSalesChannelDomainId()]);

        $domain = $this->domainRepository->search($criteria, $context)->first();

        return $this->salesChannelContextService->get(
            $domain->getSalesChannelId(),
            Uuid::randomHex(),
            null
        );
    }
}
