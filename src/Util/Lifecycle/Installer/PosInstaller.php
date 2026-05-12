<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\Installer;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelDefinition;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelInventoryDefinition;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelMediaDefinition;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelProductDefinition;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunDefinition;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunLogDefinition;

/**
 * @internal
 */
#[Package('checkout')]
class PosInstaller
{
    private Connection $connection;

    /**
     * @internal
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function removePosTables(): void
    {
        $classNames = [
            PosSalesChannelInventoryDefinition::ENTITY_NAME,
            PosSalesChannelMediaDefinition::ENTITY_NAME,
            PosSalesChannelProductDefinition::ENTITY_NAME,
            PosSalesChannelRunLogDefinition::ENTITY_NAME,
            PosSalesChannelRunDefinition::ENTITY_NAME,
            PosSalesChannelDefinition::ENTITY_NAME,
        ];

        foreach ($classNames as $className) {
            $this->connection->executeStatement(\sprintf('DROP TABLE IF EXISTS `%s`', $className));
        }
    }
}
