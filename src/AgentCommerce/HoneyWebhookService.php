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
use Shopware\Administration\Notification\NotificationCollection;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
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
     * @param EntityRepository<NotificationCollection> $notificationRepository
     */
    public function __construct(
        private readonly EntityRepository $salesChannelRepository,
        private readonly EntityRepository $notificationRepository, // @phpstan-ignore parameter.deprecatedClass, property.deprecatedClass
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

    public function register(string $salesChannelId, Context $context): bool
    {
        $salesChannel = $this->loadSalesChannel($salesChannelId, $context);
        if (!$salesChannel) {
            return false;
        }

        $productExport = $salesChannel?->getProductExports()?->first();
        $storefront = $productExport?->getStorefrontSalesChannel();
        if (!$storefront) {
            return false;
        }

        $url = $storefront->getHreflangDefaultDomain()?->getUrl() ?? $storefront->getDomains()?->first()?->getUrl();

        $routes = $this->router->getRouteCollection()->get('store-api.product.export');
        if (!$routes) {
            return false;
        }

        $path = str_replace(['{accessKey}', '{fileName}'], [$productExport->getAccessKey(), $productExport->getFileName()], $routes->getPath());

        $tokenBuilder = Builder::new(new JoseEncoder(), ChainedFormatter::default());
        $token = $tokenBuilder
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

        try {
            $response = $this->client->post('webhooks/sw/install', [
                'body' => $token,
                'timeout' => 20,
                'connect_timeout' => 20,
            ]);
            $content = json_decode($response->getBody()->getContents(), true);

            $this->systemConfigService->set(Settings::AGENT_COMMERCE_ONBOARDED, true, $salesChannel->getId());
            $this->logger->info('PayPal agent commerce onboarding successful', [
                'salesChannelId' => $salesChannel->getId(),
                'message' => $content['message'] ?? 'Some message',
            ]);
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $content = json_decode($response->getBody()->getContents(), true);

            $this->systemConfigService->set(Settings::AGENT_COMMERCE_ONBOARDED, false, $salesChannel->getId());
            $this->logger->error('PayPal agent commerce onboarding failed', [
                'salesChannelId' => $salesChannel->getId(),
                'message' => $content['message'],
            ]);
        }

        $source = $context->getSource();
        if (!$source instanceof AdminApiSource) {
            return $content['success'] === 'success';
        }

        $data = [
            'id' => Uuid::randomHex(),
            'status' => $content['success'] ? 'success' : 'error',
            'message' => 'PayPal agent commerce: ' . ($content['message'] ?? ''),
            'requiredPrivileges' => [],
            'createdByUserId' => $source->getUserId(),
        ];

        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($data): void {
            $this->notificationRepository->create([$data], $context);
        });

        return $content['success'] === 'success';
    }

    public function unregister(string $salesChannelId, Context $context): void
    {
        // TODO: create unregister call

        $this->systemConfigService->set(Settings::AGENT_COMMERCE_ONBOARDED, false, $salesChannelId);
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
