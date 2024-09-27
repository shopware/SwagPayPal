<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\Installer;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Validation\RestrictDeleteViolationException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Util\Lifecycle\Method\AbstractMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;

/**
 * @internal
 */
#[Package('checkout')]
class PaymentMethodInstaller
{
    private EntityRepository $paymentMethodRepository;

    private EntityRepository $ruleRepository;

    private PluginIdProvider $pluginIdProvider;

    private PaymentMethodDataRegistry $methodDataRegistry;

    private MediaInstaller $mediaInstaller;

    /**
     * @internal
     */
    public function __construct(
        EntityRepository $paymentMethodRepository,
        EntityRepository $ruleRepository,
        PluginIdProvider $pluginIdProvider,
        PaymentMethodDataRegistry $methodDataRegistry,
        MediaInstaller $mediaInstaller,
    ) {
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->ruleRepository = $ruleRepository;
        $this->pluginIdProvider = $pluginIdProvider;
        $this->methodDataRegistry = $methodDataRegistry;
        $this->mediaInstaller = $mediaInstaller;
    }

    public function installAll(Context $context): void
    {
        $pluginId = $this->pluginIdProvider->getPluginIdByBaseClass(SwagPayPal::class, $context);

        $upsertData = [];
        $translationData = [];
        $paymentMethods = [];
        foreach ($this->methodDataRegistry->getPaymentMethods() as $method) {
            $data = $this->getPaymentMethodData($method, $pluginId, $context);
            $upsertData[] = $data;

            // due to NEXT-12900, we write translations separately
            $translationData[] = [
                'id' => $data['id'],
                'translations' => $method->getTranslations(),
            ];

            $paymentMethods[$data['id']] = $method;
        }

        $this->paymentMethodRepository->upsert($upsertData, $context);
        $this->paymentMethodRepository->upsert($translationData, $context);

        /** @var string $paymentMethodId */
        foreach ($paymentMethods as $paymentMethodId => $method) {
            $this->mediaInstaller->installPaymentMethodMedia($method, $paymentMethodId, $context);
        }
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
        $this->mediaInstaller->installPaymentMethodMedia($method, $data['id'], $context);
    }

    public function removeRules(Context $context): void
    {
        $ruleRemovals = [];
        $paymentMethodUpdates = [];

        foreach ($this->methodDataRegistry->getPaymentMethods() as $method) {
            $entity = $this->methodDataRegistry->getEntityFromData($method, $context);
            if ($entity === null) {
                continue;
            }

            $rule = $entity->getAvailabilityRule();
            if ($rule === null) {
                continue;
            }

            if (!\preg_match('/PayPal.+AvailabilityRule/', $rule->getName())) {
                continue;
            }

            $ruleRemovals[] = ['id' => $rule->getId()];

            $paymentMethodUpdates[] = [
                'id' => $entity->getId(),
                'availabilityRuleId' => null,
            ];
        }

        if ($ruleRemovals === []) {
            return;
        }

        if ($paymentMethodUpdates !== []) {
            $this->paymentMethodRepository->update($paymentMethodUpdates, $context);
        }

        try {
            $this->ruleRepository->delete($ruleRemovals, $context);
        } catch (RestrictDeleteViolationException $e) {
        }
    }

    private function getPaymentMethodData(AbstractMethodData $method, string $pluginId, Context $context): array
    {
        $translations = $method->getTranslations();
        $defaultTranslation = $translations['en-GB'];

        $paymentMethodData = [
            'id' => Uuid::randomHex(),
            'handlerIdentifier' => $method->getHandler(),
            'name' => $defaultTranslation['name'],
            'technicalName' => $method->getTechnicalName(),
            'position' => $method->getPosition(),
            'afterOrderEnabled' => true,
            'pluginId' => $pluginId,
            'description' => $defaultTranslation['description'],
        ];

        $existingMethodId = $this->methodDataRegistry->getEntityIdFromData($method, $context);
        if ($existingMethodId) {
            $paymentMethodData['id'] = $existingMethodId;
        }

        return $paymentMethodData;
    }
}
