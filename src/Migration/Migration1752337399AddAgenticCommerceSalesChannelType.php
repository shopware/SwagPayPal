<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Migration\Traits\ImportTranslationsTrait;
use Shopware\Core\Migration\Traits\Translations;
use Swag\PayPal\SwagPayPal;

/**
 * @internal
 */
#[Package('checkout')]
class Migration1752337399AddAgenticCommerceSalesChannelType extends MigrationStep
{
    use ImportTranslationsTrait;

    public function getCreationTimestamp(): int
    {
        return 1752337399;
    }

    public function update(Connection $connection): void
    {
        $this->createSalesChannelType($connection);
        $this->createSalesChannelTypeTranslations($connection);
    }

    private function createSalesChannelType(Connection $connection): void
    {
        $type = $connection->fetchOne(
            'SELECT `id` FROM `sales_channel_type` WHERE `id` = :id',
            ['id' => Uuid::fromHexToBytes(SwagPayPal::SALES_CHANNEL_TYPE_AGENTIC_COMMERCE)]
        );

        if ($type) {
            return;
        }

        $connection->executeStatement(
            'INSERT INTO `sales_channel_type` (`id`, `cover_url`, `icon_name`, `screenshot_urls`, `created_at`) VALUES (:id, :coverUrl, :iconName, :screenshotUrls, :createdAt)',
            [
                'id' => Uuid::fromHexToBytes(SwagPayPal::SALES_CHANNEL_TYPE_AGENTIC_COMMERCE),
                'coverUrl' => null,
                'iconName' => 'regular-artificial-intelligence',
                'screenshotUrls' => null,
                'createdAt' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]
        );
    }

    private function createSalesChannelTypeTranslations(Connection $connection): void
    {
        $translations = new Translations(
            [
                'sales_channel_type_id' => Uuid::fromHexToBytes(SwagPayPal::SALES_CHANNEL_TYPE_AGENTIC_COMMERCE),
                'name' => 'PayPal Agentic Commerce',
                'manufacturer' => 'shopware AG',
                'description' => 'PayPal Agentic Commerce Sales Channel',
                'description_long' => 'Der PayPal Agentic Commerce ist eine KI Lösung, die es Kunden ermöglicht, Produkte im Chat mit einem KI Agenten zu kaufen. Ordne Produkte zu, die der Agent verkaufen soll, um das Einkaufserlebnis zu verbessern.',
            ],
            [
                'sales_channel_type_id' => Uuid::fromHexToBytes(SwagPayPal::SALES_CHANNEL_TYPE_AGENTIC_COMMERCE),
                'name' => 'PayPal Agentic Commerce',
                'manufacturer' => 'PayPal',
                'description' => 'PayPal Agentic Commerce Sales Channel',
                'description_long' => 'The PayPal Agentic Commerce is an AI solution that allows customers to purchase products in a chat with an AI agent. Assign products that the agent should sell to enhance the shopping experience.',
            ]
        );

        $this->importTranslation(
            'sales_channel_type_translation',
            $translations,
            $connection
        );
    }
}
