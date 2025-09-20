<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce;

use GuzzleHttp\Client;
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
    private Client $client;

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

    /**
     * @return array{success: bool, message: string, error?: string}
     */
    public function register(string $salesChannelId, Context $context): array
    {
        $token = $this->createToken($salesChannelId, $context);
        if (!$token) {
            return [
                'success' => false,
                'message' => 'could not create token',
            ];
        }

        try {
            $response = $this->client->post('webhooks/sw/install', [
                'body' => $token,
                'timeout' => 20,
                'connect_timeout' => 20,
            ]);
        } catch (ClientException $e) {
            $response = $e->getResponse();
        }

        $content = json_decode($response->getBody()->getContents(), true);
        $content['success'] ??= true;

        $this->systemConfigService->set(Settings::AGENT_COMMERCE_ONBOARDED, $content['success'], $salesChannelId);
        $this->logger->info('PayPal agent commerce onboarding successful', [
            'salesChannelId' => $salesChannelId,
            'success' => $content['success'],
            'message' => $content['message'],
        ]);

        return $content;
    }

    /**
     * @return array{success: bool, message: string, error?: string}
     */
    public function deregister(string $salesChannelId, Context $context): array
    {
        $token = $this->createToken($salesChannelId, $context);
        if (!$token) {
            return [
                'success' => false,
                'message' => 'could not create token',
            ];
        }

        try {
            $response = $this->client->post('webhooks/sw/uninstall', [
                'body' => $token,
                'timeout' => 20,
                'connect_timeout' => 20,
            ]);

            $this->systemConfigService->set(Settings::AGENT_COMMERCE_ONBOARDED, false, $salesChannelId);
        } catch (ClientException $e) {
            $response = $e->getResponse();
        }

        $content = json_decode($response->getBody()->getContents(), true);
        $content['success'] ??= true;

        $this->logger->info('PayPal agent commerce offboarding successful', [
            'salesChannelId' => $salesChannelId,
            'success' => $content['success'],
            'message' => $content['message'],
        ]);

        return $content;
    }

    private function createToken(string $salesChannelId, Context $context): ?string
    {
        $salesChannel = $this->loadSalesChannel($salesChannelId, $context);
        if (!$salesChannel || !$salesChannel->getActive()) {
            return null;
        }

        $productExport = $salesChannel->getProductExports()?->first();
        $storefront = $productExport?->getStorefrontSalesChannel();
        if (!$storefront) {
            return null;
        }

        $url = $storefront->getHreflangDefaultDomain()?->getUrl() ?? $storefront->getDomains()?->first()?->getUrl();

        $routes = $this->router->getRouteCollection()->get('store-api.product.export');
        if (!$routes) {
            return null;
        }

        $path = str_replace(['{accessKey}', '{fileName}'], [$productExport->getAccessKey(), $productExport->getFileName()], $routes->getPath());

        $tokenBuilder = Builder::new(new JoseEncoder(), ChainedFormatter::default());

        return $tokenBuilder
            ->withClaim('storeName', $salesChannel->getName())
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
}
