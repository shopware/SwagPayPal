<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\Repositories;

use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DefinitionInstanceRegistryMock extends DefinitionInstanceRegistry
{
    private LanguageRepoMock $languageRepo;

    private SalesChannelRepoMock $salesChannelRepo;

    private OrderTransactionRepoMock $orderTransactionRepo;

    private SystemConfigRepoMock $systemConfigRepo;

    public function __construct(array $elements, ContainerInterface $container)
    {
        parent::__construct($container, $elements, []);
        $this->languageRepo = new LanguageRepoMock();
        $this->salesChannelRepo = new SalesChannelRepoMock();
        $this->orderTransactionRepo = new OrderTransactionRepoMock();
        $this->systemConfigRepo = new SystemConfigRepoMock();
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
            case $this->systemConfigRepo->getDefinition()->getEntityName():
                return $this->systemConfigRepo;
            default:
                return parent::getRepository($entityName);
        }
    }
}
