<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Builder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
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
use Swag\PayPal\AgentCommerce\Exception\HoneyWebhookExceptions;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[Package('checkout')]
class HoneyWebhookService
{
    protected ClientInterface $client;

    /**
     * @param EntityRepository<SalesChannelCollection> $salesChannelRepository
     */
    public function __construct(
        private readonly EntityRepository $salesChannelRepository,
        private readonly CredentialsUtil $credentialsUtil,
        private readonly RouterInterface $router,
        private readonly SystemConfigService $systemConfigService,
        private readonly LoggerInterface $logger,
    ) {
        $this->client = new Client([
            'base_uri' => 'https://d.joinhoney.com/',
            'headers' => [
                'Content-Type' => 'text/plain',
            ],
        ]);
    }

    public function register(string $salesChannelId, Context $context): ResponseInterface
    {
        $salesChannel = $this->loadSalesChannel($salesChannelId, $context);
        if (!$salesChannel || !$salesChannel->getActive()) {
            throw HoneyWebhookExceptions::invalidSalesChannel();
        }

        if ($this->systemConfigService->getBool(Settings::AGENT_COMMERCE_ONBOARDED, $salesChannelId)) {
            $deregisterResult = $this->webhookCall($salesChannel, 'uninstall');

            if ($deregisterResult->getStatusCode() !== Response::HTTP_OK) {
                throw HoneyWebhookExceptions::failedDeregisterWebhook();
            }
        }

        $result = $this->webhookCall($salesChannel, 'install');
        $this->systemConfigService->set(Settings::AGENT_COMMERCE_ONBOARDED, $result->getStatusCode() === Response::HTTP_OK, $salesChannelId);

        return $result;
    }

    public function deregister(string $salesChannelId, Context $context): ResponseInterface
    {
        if (!$this->systemConfigService->getBool(Settings::AGENT_COMMERCE_ONBOARDED, $salesChannelId)) {
            throw HoneyWebhookExceptions::salesChannelNotRegistered();
        }

        $salesChannel = $this->loadSalesChannel($salesChannelId, $context);
        if (!$salesChannel) {
            throw HoneyWebhookExceptions::invalidSalesChannel();
        }

        $result = $this->webhookCall($salesChannel, 'uninstall');
        if ($result->getStatusCode() === Response::HTTP_OK) {
            $this->systemConfigService->set(Settings::AGENT_COMMERCE_ONBOARDED, false, $salesChannelId);
        }

        return $result;
    }

    private function createToken(SalesChannelEntity $salesChannel): string
    {
        $productExport = $salesChannel->getProductExports()?->first();
        if (!$productExport) {
            throw HoneyWebhookExceptions::productExportNotFound();
        }

        $storefront = $productExport->getStorefrontSalesChannel();
        if (!$storefront) {
            throw HoneyWebhookExceptions::storefrontSalesChannelNotFound();
        }

        $route = $this->router->getRouteCollection()->get('store-api.product.export');
        if (!$route) {
            throw HoneyWebhookExceptions::invalidProductExportRoute();
        }

        $path = str_replace(['{accessKey}', '{fileName}'], [$productExport->getAccessKey(), $productExport->getFileName()], $route->getPath());
        $url = $storefront->getHreflangDefaultDomain()?->getUrl() ?? $storefront->getDomains()?->first()?->getUrl();

        $tokenBuilder = Builder::new(new JoseEncoder(), ChainedFormatter::default());

        return $tokenBuilder
            ->withClaim('storeName', $salesChannel->getTranslation('name'))
            ->withClaim('storeUrl', $url)
            ->withClaim('country', $salesChannel->getCountry()?->getIso())
            ->withClaim('currency', $salesChannel->getCurrency()?->getIsoCode())
            ->withClaim('favIcon', 'https://localhost/favicon.ico') // TODO: Need to be load
            ->withClaim('shippingCountries', $storefront->getCountries()?->map(fn (CountryEntity $country) => $country->getIso()))
            ->withClaim('paypalMerchantId', $this->credentialsUtil->getMerchantPayerId($storefront->getId()))
            ->withClaim('shopwareMerchantId', $salesChannel->getId())
            ->withClaim('catalogDownloadUrl', rtrim($url ?? '', '/') . $path)
            ->getToken(new Sha256(), InMemory::plainText(random_bytes(32)))
            ->toString();
    }

    private function loadSalesChannel(string $salesChannelId, Context $context): ?SalesChannelEntity
    {
        $criteria = new Criteria([$salesChannelId]);
        $criteria->addFilter(new EqualsFilter('typeId', SwagPayPal::SALES_CHANNEL_TYPE_AGENT_COMMERCE));
        $criteria->addAssociations([
            'country',
            'currency',
            'domains',
            'productExports.storefrontSalesChannel.domains',
            'productExports.storefrontSalesChannel.hreflangDefaultDomain',
            'productExports.storefrontSalesChannel.countries',
        ]);

        return $this->salesChannelRepository->search($criteria, $context)->first();
    }

    /**
     * @throws HoneyWebhookExceptions
     */
    private function webhookCall(SalesChannelEntity $salesChannel, string $endpoint): ResponseInterface
    {
        try {
            $response = $this->client->request('POST', 'webhooks/sw/' . $endpoint, [
                'body' => $this->createToken($salesChannel),
                'timeout' => 20,
                'connect_timeout' => 20,
            ]);
        } catch (ClientException $e) {
            $response = $e->getResponse();
        } catch (HoneyWebhookExceptions $e) {
            $this->logger->error('PayPal agent commerce webhook livecycle: {message}', ['message' => $e->getMessage(), 'exception' => $e]);

            throw $e;
        }

        /** @var StreamInterface $body */
        $body = $response->getBody();
        $content = json_decode($body->getContents(), true);
        // So that the caller can also read the content
        $body->rewind();

        $content['success'] ??= !isset($content['error']);
        $data = [
            'salesChannelId' => $salesChannel->getId(),
            'success' => $content['success'],
            'message' => $content['message'],
            'error' => $content['error'] ?? null,
        ];

        $this->logger->log($content['success'] ? 'info' : 'error', 'PayPal agent commerce webhook ' . $endpoint, $data);

        return $response;
    }
}
