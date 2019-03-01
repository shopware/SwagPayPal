<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Setting\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use SwagPayPal\Setting\Exception\PayPalSettingsNotFoundException;
use SwagPayPal\Setting\SwagPayPalSettingGeneralCollection;
use SwagPayPal\Setting\SwagPayPalSettingGeneralEntity;

class SettingsProvider implements SettingsProviderInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $settingGeneralRepo;

    public function __construct(EntityRepositoryInterface $settingGeneralRepo)
    {
        $this->settingGeneralRepo = $settingGeneralRepo;
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
}
