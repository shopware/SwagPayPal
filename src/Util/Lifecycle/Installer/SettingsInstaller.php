<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\Installer;

use Psr\Log\NullLogger;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\Setting\Settings;

/**
 * @internal
 */
#[Package('checkout')]
class SettingsInstaller
{
    private EntityRepository $systemConfigRepository;

    private SystemConfigService $systemConfig;

    /**
     * @internal
     */
    public function __construct(
        EntityRepository $systemConfigRepository,
        SystemConfigService $systemConfig,
    ) {
        $this->systemConfigRepository = $systemConfigRepository;
        $this->systemConfig = $systemConfig;
    }

    public function addDefaultConfiguration(): void
    {
        if ($this->validSettingsExists()) {
            return;
        }

        foreach (Settings::DEFAULT_VALUES as $key => $value) {
            $this->systemConfig->set($key, $value);
        }
    }

    public function removeConfiguration(Context $context): void
    {
        $criteria = (new Criteria())
            ->addFilter(new ContainsFilter('configurationKey', Settings::SYSTEM_CONFIG_DOMAIN));
        $idSearchResult = $this->systemConfigRepository->searchIds($criteria, $context);

        $ids = \array_map(static function ($id) {
            return ['id' => $id];
        }, $idSearchResult->getIds());

        if ($ids === []) {
            return;
        }

        $this->systemConfigRepository->delete($ids, $context);
    }

    private function validSettingsExists(): bool
    {
        // since we don't have access to the regular service, we create it
        $validation = new SettingsValidationService($this->systemConfig, new NullLogger());

        try {
            $validation->validate();
        } catch (PayPalSettingsInvalidException $e) {
            return false;
        }

        return true;
    }
}
