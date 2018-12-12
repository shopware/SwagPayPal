<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Setting\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use SwagPayPal\Setting\Exception\PayPalSettingsNotFoundException;
use SwagPayPal\Setting\SwagPayPalSettingGeneralCollection;
use SwagPayPal\Setting\SwagPayPalSettingGeneralEntity;

class SettingsProvider implements SettingsProviderInterface
{
    /**
     * @var RepositoryInterface
     */
    private $settingGeneralRepo;

    public function __construct(RepositoryInterface $settingGeneralRepo)
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
        if ($settingsCollection->count() === 0) {
            throw new PayPalSettingsNotFoundException();
        }

        return $settingsCollection->first();
    }
}
