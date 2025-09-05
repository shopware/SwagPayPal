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
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Notification\NotificationCollection;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[Package('checkout')]
class WebhookService
{
    private Client $client;

    /**
     * @param EntityRepository<NotificationCollection> $notificationRepository
     */
    public function __construct(
        private readonly EntityRepository $notificationRepository, // @phpstan-ignore parameter.deprecatedClass, property.deprecatedClass
        private readonly CredentialsUtil $credentialsUtil,
        private readonly RouterInterface $router,
    ) {
        $this->client = new Client([
            'base_uri' => 'https://d.joinhoney.com/',
            'headers' => [
                'Content-Type' => 'text/plain',
            ],
        ]);
    }

    public function register(SalesChannelEntity $salesChannel, Context $context): void
    {
        $productExport = $salesChannel->getProductExports()?->first();
        $storefront = $productExport?->getStorefrontSalesChannel();
        if (!$storefront) {
            return;
        }

        $url = $storefront->getHreflangDefaultDomain()?->getUrl() ?? $storefront->getDomains()?->first()?->getUrl();

        $routes = $this->router->getRouteCollection()->get('store-api.product.export');
        if (!$routes) {
            return;
        }

        $path = str_replace(['{accessKey}', '{fileName}'], [$productExport->getAccessKey(), $productExport->getFileName()], $routes->getPath());

        $tokenBuilder = Builder::new(new JoseEncoder(), ChainedFormatter::default());
        $token = $tokenBuilder
            ->withClaim('storeName', $salesChannel->getName())
            ->withClaim('storeUrl', $url)
            ->withClaim('country', $salesChannel->getCountry()?->getIso())
            ->withClaim('currency', $salesChannel->getCurrency()?->getIsoCode())
            ->withClaim('favIcon', 'https://localhost/favicon.ico') // TODO: Need to be load
            ->withClaim('shippingCountries', $salesChannel->getCountries()?->map(fn (CountryEntity $country) => $country->getIso()))
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
        } catch (ClientException $e) {
            $response = $e->getResponse();
        }

        $source = $context->getSource();
        if (!$source instanceof AdminApiSource) {
            return;
        }

        $content = json_decode($response->getBody()->getContents(), true);
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
    }

    public function unregister(SalesChannelEntity $salesChannel, Context $context): void
    {
        // TODO: coming soon
    }
}
