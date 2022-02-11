<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Setting\Service;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Swag\PayPal\Checkout\Payment\Method\ACDCHandler;
use Swag\PayPal\Checkout\Payment\Method\PUIHandler;
use Swag\PayPal\RestApi\V1\Resource\MerchantIntegrationsResource;
use Swag\PayPal\Setting\Service\MerchantIntegrationsService;
use Swag\PayPal\Test\Helper\ServicesTrait;

class MerchantIntegrationsServiceTest extends TestCase
{
    use ServicesTrait;

    public const STATUS_ACTIVE = 'ACTIVE';

    /**
     * @var Context
     */
    private $context;

    public function setUp(): void
    {
        $this->context = Context::createDefaultContext();
    }

    public function testFetchMerchantIntegrations(): void
    {
        $merchantIntegrationService = $this->createMerchantIntegrationService();
        $integrations = $merchantIntegrationService->fetchMerchantIntegrations(null, $this->context);

        $integrationsCount = \count($integrations);
        static::assertSame(2, $integrationsCount);
    }

    public function testACDCShouldBeActive(): void
    {
        $paymentMethodId = $this->getPaymentIdByHandler(ACDCHandler::class);

        $merchantIntegrationService = $this->createMerchantIntegrationService();
        $integrations = $merchantIntegrationService->fetchMerchantIntegrations(null, $this->context);

        $integrationStatus = $integrations[$paymentMethodId];
        static::assertSame(self::STATUS_ACTIVE, $integrationStatus);
    }

    public function testPUIShouldBeUnkown(): void
    {
        $paymentMethodId = $this->getPaymentIdByHandler(PUIHandler::class);

        $merchantIntegrationService = $this->createMerchantIntegrationService();
        $integrations = $merchantIntegrationService->fetchMerchantIntegrations(null, $this->context);

        $integrationStatus = $integrations[$paymentMethodId];
        static::assertSame(MerchantIntegrationsService::UNKNOWN_STATUS, $integrationStatus);
    }

    private function createMerchantIntegrationService(): MerchantIntegrationsService
    {
        /** @var EntityRepositoryInterface $paymentRepository */
        $paymentRepository = $this->getContainer()->get('payment_method.repository');

        return new MerchantIntegrationsService(
            new MerchantIntegrationsResource($this->createPayPalClientFactory()),
            $this->createDefaultSystemConfig(),
            $paymentRepository
        );
    }

    private function getPaymentIdByHandler(string $handlerIdentifier): string
    {
        /** @var EntityRepositoryInterface $paymentRepository */
        $paymentRepository = $this->getContainer()->get('payment_method.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('handlerIdentifier', $handlerIdentifier));

        $context = Context::createDefaultContext();

        $firstId = $paymentRepository->searchIds($criteria, $context)->firstId();

        if ($firstId === null) {
            throw new \RuntimeException('No handlerIdentifier found.');
        }

        return $firstId;
    }
}
