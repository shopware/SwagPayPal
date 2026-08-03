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
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\SwagPayPal;
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
        private readonly CredentialsUtil $credentialsUtil,
        private readonly RouterInterface $router,
        private readonly SystemConfigService $systemConfigService,
        private readonly LoggerInterface $logger,
        private readonly FaviconLoader $faviconLoader,
    ) {
    }

    public function register(string $salesChannelId, Context $context): HoneyWebhookResult
    {
        try {
            $this->deregister($salesChannelId);
        } catch (HoneyWebhookException) {
            // Is logged already
        }

        $token = $this->createToken($salesChannelId, $context);
        $result = $this->webhookCall($token, 'install');
        if ($result->success) {
            $this->systemConfigService->set(Settings::AGENTIC_COMMERCE_ONBOARDED, $token, $salesChannelId);
        } else {
            $this->systemConfigService->delete(Settings::AGENTIC_COMMERCE_ONBOARDED, $salesChannelId);

            throw HoneyWebhookException::invalidRequest($result);
        }

        return $result;
    }

    public function deregister(string $salesChannelId): HoneyWebhookResult
    {
        try {
            $oldToken = $this->systemConfigService->get(Settings::AGENTIC_COMMERCE_ONBOARDED, $salesChannelId);
            if (!\is_string($oldToken)) {
                throw HoneyWebhookException::salesChannelNotRegistered();
            }

            $result = $this->webhookCall($oldToken, 'uninstall');
            $this->systemConfigService->delete(Settings::AGENTIC_COMMERCE_ONBOARDED, $salesChannelId);
            if (!$result->success) {
                throw HoneyWebhookException::invalidRequest($result);
            }

            return $result;
        } catch (HoneyWebhookException $e) {
            $this->logger->error('PayPal agentic commerce webhook livecycle: {message}', ['message' => $e->getMessage(), 'exception' => $e]);

            throw $e;
        }
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
            $this->logger->error('PayPal agentic commerce cannot create token: {message}', ['message' => $e->getMessage(), 'exception' => $e]);

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

        $this->logger->log($result->success ? 'info' : 'error', 'PayPal agentic commerce webhook ' . $endpoint, $result->jsonSerialize());

        return $result;
    }
}
