<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\Payment\Method\ACDCHandler;
use Swag\PayPal\Checkout\Payment\Method\PUIHandler;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;
use Swag\PayPal\RestApi\V1\Resource\MerchantIntegrationsResource;
use Swag\PayPal\Setting\Settings;

class MerchantIntegrationsService implements MerchantIntegrationsServiceInterface
{
    public const METHOD_MAPPING = [
        ACDCHandler::class => 'CUSTOM_CARD_PROCESSING',
        PUIHandler::class => 'PAY_UPON_INVOICE',
    ];

    public const UNKNOWN_STATUS = 'UNKNOWN';

    private MerchantIntegrationsResource $merchantIntegrationsResource;

    private SystemConfigService $systemConfigService;

    private EntityRepositoryInterface $paymentRepository;

    public function __construct(
        MerchantIntegrationsResource $merchantIntegrationsResource,
        SystemConfigService $systemConfigService,
        EntityRepositoryInterface $paymentRepository
    ) {
        $this->merchantIntegrationsResource = $merchantIntegrationsResource;
        $this->systemConfigService = $systemConfigService;
        $this->paymentRepository = $paymentRepository;
    }

    public function fetchMerchantIntegrations(?string $salesChannelId = null, Context $context): array
    {
        $sandboxActive = $this->systemConfigService->getBool(Settings::SANDBOX, $salesChannelId);

        try {
            $integrations = $this->merchantIntegrationsResource->get($salesChannelId, $sandboxActive);
        } catch (\Throwable $e) {
            // just catch exceptions thrown in case of invalid credentials
            $integrations = null;
        }

        return $this->handleIntegrations($integrations, $context);
    }

    private function handleIntegrations(?MerchantIntegrations $integrations, Context $context): array
    {
        $methods = [];

        foreach (self::METHOD_MAPPING as $handler => $necessaryCapability) {
            $paymentMethodId = $this->getPaymentMethodId($handler, $context);

            if ($integrations) {
                foreach ($integrations->getCapabilities() as $capability) {
                    if ($capability->getName() === $necessaryCapability) {
                        $methods[$paymentMethodId] = $methods[$paymentMethodId] = $capability->getStatus();
                    }
                }
            }

            if (!isset($methods[$paymentMethodId])) {
                $methods[$paymentMethodId] = self::UNKNOWN_STATUS;
            }
        }

        return $methods;
    }

    private function getPaymentMethodId(string $handlerIdentifier, Context $context): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('handlerIdentifier', $handlerIdentifier));

        $firstId = $this->paymentRepository->searchIds($criteria, $context)->firstId();

        if ($firstId === null) {
            throw new \RuntimeException('No handlerIdentifier found.');
        }

        return $firstId;
    }
}
