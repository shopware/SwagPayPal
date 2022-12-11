<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Setting\Service;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Swag\PayPal\Checkout\Payment\Method\ACDCHandler;
use Swag\PayPal\Checkout\Payment\Method\PUIHandler;
use Swag\PayPal\RestApi\V1\Resource\MerchantIntegrationsResource;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Setting\Service\MerchantIntegrationsService;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetResourceMerchantIntegrations;
use Swag\PayPal\Test\Mock\PayPal\Client\PayPalClientFactoryMock;
use Swag\PayPal\Util\Lifecycle\Method\AbstractMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;

class MerchantIntegrationsServiceTest extends TestCase
{
    use ServicesTrait;

    public function testFetchMerchantIntegrations(): void
    {
        $merchantIntegrationService = $this->createMerchantIntegrationService();

        $integrations = $merchantIntegrationService->getMerchantInformation(Context::createDefaultContext())->getCapabilities();
        static::assertCount(\count($this->getContainer()->get(PaymentMethodDataRegistry::class)->getPaymentMethods()), $integrations);
    }

    public function testACDCShouldBeActive(): void
    {
        $paymentMethodId = $this->getPaymentIdByHandler(ACDCHandler::class);

        $merchantIntegrationService = $this->createMerchantIntegrationService();

        $integrations = $merchantIntegrationService->getMerchantInformation(Context::createDefaultContext())->getCapabilities();
        static::assertSame(AbstractMethodData::CAPABILITY_ACTIVE, $integrations[$paymentMethodId]);
    }

    public function testPUIShouldBeUnknown(): void
    {
        $paymentMethodId = $this->getPaymentIdByHandler(PUIHandler::class);

        $merchantIntegrationService = $this->createMerchantIntegrationService();

        $integrations = $merchantIntegrationService->getMerchantInformation(Context::createDefaultContext())->getCapabilities();
        static::assertSame(AbstractMethodData::CAPABILITY_INELIGIBLE, $integrations[$paymentMethodId]);
    }

    public function testMerchantInformation(): void
    {
        $merchantIntegrationService = $this->createMerchantIntegrationService();

        $information = $merchantIntegrationService->getMerchantInformation(Context::createDefaultContext())->getMerchantIntegrations();
        static::assertNotNull($information);
        static::assertSame(GetResourceMerchantIntegrations::TRACKING_ID, $information->getTrackingId());
        static::assertSame(GetResourceMerchantIntegrations::LEGAL_NAME, $information->getLegalName());
    }

    private function createMerchantIntegrationService(): MerchantIntegrationsService
    {
        return new MerchantIntegrationsService(
            new MerchantIntegrationsResource($this->createPayPalClientFactory()),
            new CredentialsUtil($this->createDefaultSystemConfig()),
            $this->getContainer()->get(PaymentMethodDataRegistry::class),
            new PayPalClientFactoryMock($this->createDefaultSystemConfig(), new NullLogger())
        );
    }

    private function getPaymentIdByHandler(string $handlerIdentifier): string
    {
        /** @var EntityRepository $paymentRepository */
        $paymentRepository = $this->getContainer()->get('payment_method.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('handlerIdentifier', $handlerIdentifier));

        $firstId = $paymentRepository->searchIds($criteria, Context::createDefaultContext())->firstId();

        if ($firstId === null) {
            throw new \RuntimeException('No handlerIdentifier found.');
        }

        return $firstId;
    }
}
