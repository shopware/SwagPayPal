<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Swag\PayPal\Setting\Exception\PayPalSettingsNotFoundException;
use Swag\PayPal\Setting\SwagPayPalSettingGeneralCollection;
use Swag\PayPal\Setting\SwagPayPalSettingGeneralDefinition;
use Swag\PayPal\Setting\SwagPayPalSettingGeneralEntity;

class SettingsService implements SettingsServiceInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $settingGeneralRepo;

    public function __construct(DefinitionRegistry $definitionRegistry)
    {
        $this->settingGeneralRepo = $definitionRegistry->getRepository(SwagPayPalSettingGeneralDefinition::getEntityName());
    }

    /**
     * @throws PayPalSettingsNotFoundException
     */
    public function getSettings(Context $context): SwagPayPalSettingGeneralEntity
    {
        /** @var SwagPayPalSettingGeneralCollection $settingsCollection */
        $settingsCollection = $this->settingGeneralRepo->search(new Criteria(), $context)->getEntities();
        $settingsEntity = $settingsCollection->first();
        if ($settingsEntity === null) {
            throw new PayPalSettingsNotFoundException();
        }

        return $settingsEntity;
    }

    public function updateSettings(array $updateData, Context $context): void
    {
        $this->settingGeneralRepo->update([$updateData], $context);
    }
}
