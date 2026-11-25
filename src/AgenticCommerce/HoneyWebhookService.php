<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgenticCommerce;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Builder;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\AgenticCommerce\Exception\HoneyWebhookException;
use Swag\PayPal\AgenticCommerce\Util\FaviconLoader;
use Swag\PayPal\Setting\Service\CredentialsUtilInterface;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Util\Log\Log;
use Swag\PayPal\Util\Log\LogCode;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[Package('checkout')]
class HoneyWebhookService
{
    /**
     * @param EntityRepository<SalesChannelCollection> $salesChannelRepository
     */
    public function __construct(
        private readonly ClientInterface $client,
        private readonly EntityRepository $salesChannelRepository,
        private readonly CredentialsUtilInterface $credentialsUtil,
        private readonly RouterInterface $router,
        private readonly SystemConfigService $systemConfigService,
        private readonly LoggerInterface $logger,
        private readonly FaviconLoader $faviconLoader,
    ) {
    }

    public function register(string $salesChannelId, Context $context): HoneyWebhookResult
    {
        $previous = $this->uninstall($salesChannelId);
        if ($previous && !$previous->success) {
            $this->logger->warning('Could not remove the previous registration of the sales channel at PayPal\'s agentic commerce service, registering it again anyway', Log::context(
                code: LogCode::AGENTIC_OLD_REGISTRATION_NOT_REMOVED,
                salesChannelId: $salesChannelId,
                errorCode: $previous->error,
                resultMessage: $previous->message,
                exception: $previous->exception,
            ));
        }

        $token = $this->createToken($salesChannelId, $context);
        $result = $this->webhookCall($token, 'install');
        if (!$result->success) {
            $this->systemConfigService->delete(Settings::AGENTIC_COMMERCE_ONBOARDED, $salesChannelId);
            $this->logger->error('Could not notify PayPal\'s agentic commerce service: {endpoint}, so PayPal doesn\'t know the current registration state of the sales channel', Log::context(
                code: LogCode::AGENTIC_WEBHOOK_FAILED,
                endpoint: 'install',
                errorCode: $result->error,
                resultMessage: $result->message,
                exception: $result->exception,
            ));

            throw HoneyWebhookException::invalidRequest($result);
        }

        $this->systemConfigService->set(Settings::AGENTIC_COMMERCE_ONBOARDED, $token, $salesChannelId);

        return $result;
    }

    public function deregister(string $salesChannelId): HoneyWebhookResult
    {
        $result = $this->uninstall($salesChannelId);
        if ($result === null) {
            $this->logger->debug('Did not deregister the sales channel at PayPal\'s agentic commerce service, because it is not registered', Log::context(
                salesChannelId: $salesChannelId,
            ));

            throw HoneyWebhookException::salesChannelNotRegistered();
        }

        if (!$result->success) {
            $this->logger->error('Could not notify PayPal\'s agentic commerce service: {endpoint}, so PayPal doesn\'t know the current registration state of the sales channel', Log::context(
                code: LogCode::AGENTIC_WEBHOOK_FAILED,
                endpoint: 'uninstall',
                errorCode: $result->error,
                resultMessage: $result->message,
                exception: $result->exception,
            ));

            throw HoneyWebhookException::invalidRequest($result);
        }

        return $result;
    }

    /**
     * Null if the sales channel isn't registered, the callers log failures.
     */
    private function uninstall(string $salesChannelId): ?HoneyWebhookResult
    {
        $token = $this->systemConfigService->get(Settings::AGENTIC_COMMERCE_ONBOARDED, $salesChannelId);
        if (!\is_string($token)) {
            return null;
        }

        $result = $this->webhookCall($token, 'uninstall');
        $this->systemConfigService->delete(Settings::AGENTIC_COMMERCE_ONBOARDED, $salesChannelId);

        return $result;
    }

    private function createToken(string $salesChannelId, Context $context): string
    {
        try {
            $salesChannel = $this->loadSalesChannel($salesChannelId, $context);
            if (!$salesChannel || !$salesChannel->getActive()) {
                throw HoneyWebhookException::invalidSalesChannel();
            }

            $productExport = $salesChannel->getProductExports()?->first();
            if (!$productExport) {
                throw HoneyWebhookException::productExportNotFound();
            }

            $storefront = $productExport->getStorefrontSalesChannel();
            if (!$storefront) {
                throw HoneyWebhookException::storefrontSalesChannelNotFound();
            }

            $route = $this->router->getRouteCollection()->get('store-api.product.export');
            if (!$route) {
                throw HoneyWebhookException::invalidProductExportRoute();
            }

            $path = str_replace(['{accessKey}', '{fileName}'], [$productExport->getAccessKey(), $productExport->getFileName()], $route->getPath());
            $url = $storefront->getHreflangDefaultDomain()?->getUrl() ?? $storefront->getDomains()?->first()?->getUrl();

            return Builder::new(new JoseEncoder(), ChainedFormatter::default())
                ->withClaim('storeName', $salesChannel->getTranslation('name'))
                ->withClaim('storeUrl', $url)
                ->withClaim('country', $salesChannel->getCountry()?->getIso())
                ->withClaim('currency', $salesChannel->getCurrency()?->getIsoCode())
                ->withClaim('favIcon', $this->faviconLoader->loadFaviconLink($storefront->getId(), $context))
                ->withClaim('shippingCountries', array_values($storefront->getCountries()?->map(static fn (CountryEntity $country) => $country->getIso()) ?? []))
                ->withClaim('paypalMerchantId', $this->credentialsUtil->getMerchantPayerId($storefront->getId()))
                ->withClaim('shopwareMerchantId', $salesChannel->getId())
                ->withClaim('catalogDownloadUrl', rtrim($url ?? '', '/') . $path)
                ->getToken(new Sha256(), InMemory::plainText(random_bytes(32)))
                ->toString();
        } catch (HoneyWebhookException $e) {
            $this->logger->error('Could not create the token for PayPal\'s agentic commerce service: {errorCode}', Log::context(
                code: LogCode::AGENTIC_TOKEN_FAILED,
                salesChannelId: $salesChannelId,
                exception: $e,
            ));

            throw $e;
        }
    }

    private function loadSalesChannel(string $salesChannelId, Context $context): ?SalesChannelEntity
    {
        $criteria = new Criteria([$salesChannelId]);
        $criteria->addFilter(new EqualsFilter('typeId', SwagPayPal::SALES_CHANNEL_TYPE_AGENTIC_COMMERCE));
        $criteria->addAssociations([
            'country',
            'currency',
            'domains',
            'productExports.storefrontSalesChannel.domains',
            'productExports.storefrontSalesChannel.hreflangDefaultDomain',
            'productExports.storefrontSalesChannel.countries',
        ]);

        return $this->salesChannelRepository->search($criteria, $context)->getEntities()->first();
    }

    private function webhookCall(string $token, string $endpoint): HoneyWebhookResult
    {
        try {
            $response = $this->client->request('POST', 'webhooks/sw/' . $endpoint, ['body' => $token]);
        } catch (ClientException $e) {
            $response = $e->getResponse();
        }

        $content = json_decode($response->getBody()->getContents(), true);
        $result = new HoneyWebhookResult($content['success'] ?? !isset($content['error']), $content['message'], $content['error'] ?? null, $e ?? null);

        if ($result->success) {
            $this->logger->info('Notified PayPal\'s agentic commerce service: {endpoint}', Log::context(
                code: LogCode::AGENTIC_WEBHOOK_SENT,
                endpoint: $endpoint,
            ));
        }

        return $result;
    }
}
