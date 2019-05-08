<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Mock\Repositories;

use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DefinitionInstanceRegistryMock extends DefinitionInstanceRegistry
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
     * @var OrderTransactionRepoMock
     */
    private $orderTransactionRepo;

    /**
     * @var SwagPayPalSettingGeneralRepoMock
     */
    private $swagPayPalSettingGeneralRepo;

    public function __construct(array $elements, ContainerInterface $container)
    {
        parent::__construct($container, $elements, []);
        $this->languageRepo = new LanguageRepoMock();
        $this->salesChannelRepo = new SalesChannelRepoMock();
        $this->orderTransactionRepo = new OrderTransactionRepoMock();
        $this->swagPayPalSettingGeneralRepo = new SwagPayPalSettingGeneralRepoMock();
    }

    /**
     * @return EntityRepositoryInterface|OrderTransactionRepoMock
     */
    public function getRepository(string $entityName): EntityRepositoryInterface
    {
        switch ($entityName) {
            case $this->languageRepo->getDefinition()->getEntityName():
                return $this->languageRepo;
            case $this->salesChannelRepo->getDefinition()->getEntityName():
                return $this->salesChannelRepo;
            case $this->orderTransactionRepo->getDefinition()->getEntityName():
                return $this->orderTransactionRepo;
            case $this->swagPayPalSettingGeneralRepo->getDefinition()->getEntityName():
                return $this->swagPayPalSettingGeneralRepo;
            default:
                return parent::getRepository($entityName);
        }
    }
}
