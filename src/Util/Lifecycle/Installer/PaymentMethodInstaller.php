<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\Installer;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Util\Lifecycle\Method\AbstractMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;

class PaymentMethodInstaller
{
    private EntityRepositoryInterface $paymentMethodRepository;

    private EntityRepositoryInterface $ruleRepository;

    private PluginIdProvider $pluginIdProvider;

    private PaymentMethodDataRegistry $methodDataRegistry;

    public function __construct(
        EntityRepositoryInterface $paymentMethodRepository,
        EntityRepositoryInterface $ruleRepository,
        PluginIdProvider $pluginIdProvider,
        PaymentMethodDataRegistry $methodDataRegistry
    ) {
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->ruleRepository = $ruleRepository;
        $this->pluginIdProvider = $pluginIdProvider;
        $this->methodDataRegistry = $methodDataRegistry;
    }

    public function installAll(Context $context): void
    {
        $pluginId = $this->pluginIdProvider->getPluginIdByBaseClass(SwagPayPal::class, $context);

        $upsertData = [];
        $translationData = [];
        foreach ($this->methodDataRegistry->getPaymentMethods() as $method) {
            $data = $this->getPaymentMethodData($method, $pluginId, $context);
            $upsertData[] = $data;

            // due to NEXT-12900, we write translations separately
            $translationData[] = [
                'id' => $data['id'],
                'translations' => $method->getTranslations(),
            ];
        }

        $this->paymentMethodRepository->upsert($upsertData, $context);
        $this->paymentMethodRepository->upsert($translationData, $context);
    }

    /**
     * @param class-string<AbstractMethodData> $methodDataClass
     */
    public function install(string $methodDataClass, Context $context): void
    {
        $method = $this->methodDataRegistry->getPaymentMethod($methodDataClass);
        $this->installMethod($method, $context);
    }

    public function installMethod(AbstractMethodData $method, Context $context): void
    {
        $pluginId = $this->pluginIdProvider->getPluginIdByBaseClass(SwagPayPal::class, $context);

        $data = $this->getPaymentMethodData($method, $pluginId, $context);

        // due to NEXT-12900, we write translations separately
        $translationData = [
            'id' => $data['id'],
            'translations' => $method->getTranslations(),
        ];

        $this->paymentMethodRepository->upsert([$data], $context);
        $this->paymentMethodRepository->upsert([$translationData], $context);
    }

    public function removeRules(Context $context): void
    {
        $ruleRemovals = [];
        $paymentMethodUpdates = [];

        foreach ($this->methodDataRegistry->getPaymentMethods() as $method) {
            $rule = $this->getRule($method, $context);

            if ($rule === null || !isset($rule['id'])) {
                continue;
            }

            $ruleRemovals[] = ['id' => $rule['id']];
            $existingId = $this->methodDataRegistry->getEntityIdFromData($method, $context);

            if ($existingId === null) {
                continue;
            }

            $paymentMethodUpdates[] = [
                'id' => $existingId,
                'availabilityRuleId' => null,
            ];
        }

        if ($ruleRemovals === []) {
            return;
        }

        if ($paymentMethodUpdates !== []) {
            $this->paymentMethodRepository->update($paymentMethodUpdates, $context);
        }

        $this->ruleRepository->delete($ruleRemovals, $context);
    }

    private function getPaymentMethodData(AbstractMethodData $method, string $pluginId, Context $context): array
    {
        $translations = $method->getTranslations();
        $defaultTranslation = $translations['en-GB'];

        $paymentMethodData = [
            'id' => Uuid::randomHex(),
            'handlerIdentifier' => $method->getHandler(),
            'name' => $defaultTranslation['name'],
            'position' => $method->getPosition(),
            'afterOrderEnabled' => true,
            'pluginId' => $pluginId,
            'description' => $defaultTranslation['description'],
        ];

        if ($rule = $this->getRule($method, $context)) {
            $paymentMethodData['availabilityRule'] = $rule;
        }

        if ($existingId = $this->methodDataRegistry->getEntityIdFromData($method, $context)) {
            $paymentMethodData['id'] = $existingId;
        }

        return $paymentMethodData;
    }

    private function getRule(AbstractMethodData $method, Context $context): ?array
    {
        $data = $method->getRuleData($context);
        if ($data === null || !$data['name']) {
            return null;
        }

        $criteria = (new Criteria())->addFilter(new EqualsFilter('name', $data['name']));

        $ruleId = $this->ruleRepository->searchIds($criteria, $context)->firstId();
        if ($ruleId !== null) {
            $data['id'] = $ruleId;
        }

        return $data;
    }
}
