<?php declare(strict_types=1);

namespace SwagPayPal\Test\Mock\Repositories;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\Language\LanguageDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use SwagPayPal\Setting\SwagPayPalSettingGeneralDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DefinitionRegistryMock extends DefinitionRegistry
{
    /**
     * @var LanguageRepoMock
     */
    private $languageRepo;

    /**
     * @var SalesChannelRepoMock
     */
    private $salesChannelRepo;

    /**
     * @var OrderRepoMock
     */
    private $orderRepo;

    /**
     * @var OrderTransactionRepoMock
     */
    private $orderTransactionRepo;

    /**
     * @var SwagPayPalSettingGeneralRepoMock
     */
    private $swagPayPalSettingGeneralRepo;

    public function __construct(array $elements, ContainerInterface $container)
    {
        parent::__construct($elements, $container);
        $this->languageRepo = new LanguageRepoMock();
        $this->salesChannelRepo = new SalesChannelRepoMock();
        $this->orderRepo = new OrderRepoMock();
        $this->orderTransactionRepo = new OrderTransactionRepoMock();
        $this->swagPayPalSettingGeneralRepo = new SwagPayPalSettingGeneralRepoMock();
    }

    /**
     * @return EntityRepositoryInterface|OrderTransactionRepoMock
     */
    public function getRepository(string $entityName): EntityRepositoryInterface
    {
        switch ($entityName) {
            case LanguageDefinition::getEntityName():
                return $this->languageRepo;
            case SalesChannelDefinition::getEntityName():
                return $this->salesChannelRepo;
            case OrderDefinition::getEntityName():
                return $this->orderRepo;
            case OrderTransactionDefinition::getEntityName():
                return $this->orderTransactionRepo;
            case SwagPayPalSettingGeneralDefinition::getEntityName():
                return $this->swagPayPalSettingGeneralRepo;
            default:
                return parent::getRepository($entityName);
        }
    }
}
