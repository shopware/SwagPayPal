<?php declare(strict_types=1);

namespace Swag\PayPal\Util\Lifecycle;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\Payment\PayPalPaymentHandler;
use Swag\PayPal\Payment\PayPalPuiPaymentHandler;
use Swag\PayPal\Setting\Service\SettingsService;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Util\PaymentMethodUtil;

class InstallUninstall
{
    /**
     * @var EntityRepositoryInterface
     */
    private $systemConfigRepository;

    /**
     * @var PluginIdProvider
     */
    private $pluginIdProvider;

    /**
     * @var EntityRepositoryInterface
     */
    private $paymentRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $salesChannelRepository;

    /**
     * @var string
     */
    private $className;

    public function __construct(
        EntityRepositoryInterface $systemConfigRepository,
        PluginIdProvider $pluginIdProvider,
        EntityRepositoryInterface $paymentRepository,
        EntityRepositoryInterface $salesChannelRepository,
        string $className
    ) {
        $this->systemConfigRepository = $systemConfigRepository;
        $this->pluginIdProvider = $pluginIdProvider;
        $this->paymentRepository = $paymentRepository;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->className = $className;
    }

    public function install(Context $context): void
    {
        $this->addDefaultConfiguration();
        $this->addPaymentMethods($context);
    }

    public function uninstall(Context $context): void
    {
        $this->removeConfiguration($context);
    }

    private function addDefaultConfiguration(): void
    {
        $data = [];
        foreach ((new SwagPayPalSettingStruct())->jsonSerialize() as $key => $value) {
            if ($value === null || $value === []) {
                continue;
            }

            $key = SettingsService::SYSTEM_CONFIG_DOMAIN . $key;
            $data[] = [
                'id' => Uuid::randomHex(),
                'configurationKey' => $key,
                'configurationValue' => $value,
            ];
        }
        $this->systemConfigRepository->upsert($data, Context::createDefaultContext());
    }

    private function removeConfiguration(Context $context): void
    {
        $criteria = (new Criteria())
            ->addFilter(new ContainsFilter('configurationKey', SettingsService::SYSTEM_CONFIG_DOMAIN));
        $idSearchResult = $this->systemConfigRepository->searchIds($criteria, $context);

        $ids = array_map(static function ($id) {
            return ['id' => $id];
        }, $idSearchResult->getIds());

        $this->systemConfigRepository->delete($ids, $context);
    }

    private function addPaymentMethods(Context $context): void
    {
        $pluginId = $this->pluginIdProvider->getPluginIdByBaseClass($this->className, $context);
        $paymentMethodUtil = new PaymentMethodUtil($this->paymentRepository, $this->salesChannelRepository);

        $data = [];

        $paypalData = [
            'handlerIdentifier' => PayPalPaymentHandler::class,
            'name' => 'PayPal',
            'position' => -100,
            'pluginId' => $pluginId,
            'translations' => [
                'de-DE' => [
                    'description' => 'Bezahlung per PayPal - einfach, schnell und sicher.',
                ],
                'en-GB' => [
                    'description' => 'Payment via PayPal - easy, fast and secure.',
                ],
            ],
        ];

        $payPalPaymentMethodId = $paymentMethodUtil->getPayPalPaymentMethodId($context);
        if ($payPalPaymentMethodId === null) {
            $data[] = $paypalData;
        } else {
            $paypalData['id'] = $payPalPaymentMethodId;
            $data[] = $paypalData;
        }

        $puiData = [
            'handlerIdentifier' => PayPalPuiPaymentHandler::class,
            'position' => -99,
            'active' => false,
            'pluginId' => $pluginId,
            'translations' => [
                'de-DE' => [
                    'name' => 'Rechnungskauf',
                    'description' => 'Kaufen Sie ganz bequem auf Rechnung und bezahlen Sie später.',
                ],
                'en-GB' => [
                    'name' => 'Pay upon invoice',
                    'description' => 'Buy comfortably on invoice and pay later.',
                ],
            ],
        ];

        $payPalPuiPaymentMethodId = $paymentMethodUtil->getPayPalPuiPaymentMethodId($context);
        if ($payPalPuiPaymentMethodId === null) {
            $data[] = $puiData;
        } else {
            $puiData['id'] = $payPalPuiPaymentMethodId;
            $data[] = $puiData;
        }

        $this->paymentRepository->upsert($data, $context);
    }
}
