<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Subscriber;

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
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Notification\NotificationService;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('checkout')]
class WebhookSubscriber implements EventSubscriberInterface
{
    /**
     * @param EntityRepository<SalesChannelCollection> $salesChannelRepository
     */
    public function __construct(
        private readonly EntityRepository $salesChannelRepository,
        private readonly NotificationService $notificationService,
        private readonly CredentialsUtil $credentialsUtil,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'sales_channel.written' => 'webhook',
        ];
    }

    public function webhook(EntityWrittenEvent $event): void
    {
        $mapped = [];
        foreach ($event->getWriteResults() as $writeResult) {
            $active = $writeResult->getProperty('active');
            if ($active === null || $writeResult->getOperation() === EntityWriteResult::OPERATION_DELETE) {
                continue;
            }

            $mapped[$writeResult->getPrimaryKey()] = $active;
        }

        $criteria = new Criteria(array_keys($mapped));
        $criteria->addFilter(new EqualsFilter('typeId', SwagPayPal::SALES_CHANNEL_TYPE_AGENT_COMMERCE));
        $criteria->addAssociations([
            'country',
            'countries',
            'currency',
            'domains',
            'productExports.storefrontSalesChannel.domains',
            'productExports.storefrontSalesChannel.hreflangDefaultDomain',
        ]);

        $result = $this->salesChannelRepository->search($criteria, $event->getContext());
        foreach ($result as $salesChannel) {
            if ($mapped[$salesChannel->getId()]) {
                $this->onboard($salesChannel, $event->getContext());
            } else {
                $this->unregister($salesChannel, $event->getContext());
            }
        }
    }

    private function onboard(SalesChannelEntity $salesChannel, Context $context): void
    {
        $productExport = $salesChannel->getProductExports()?->first();
        $storefront = $productExport->getStorefrontSalesChannel();
        if (!$storefront) {
            throw new \RuntimeException('Storefront not found');
        }

        $url = $storefront->getHreflangDefaultDomain()?->getUrl() ?? $storefront->getDomains()?->first()?->getUrl();

        $tokenBuilder = (new Builder(new JoseEncoder(), ChainedFormatter::default()));
        $token = $tokenBuilder
            ->withClaim('storeName', $salesChannel->getName())
            ->withClaim('storeUrl', $url)
            ->withClaim('country', $salesChannel->getCountry()?->getIso())
            ->withClaim('currency', $salesChannel->getCurrency()?->getIsoCode())
            ->withClaim('favIcon', 'https://localhost/favicon.ico') // TODO: Need to be load
            ->withClaim('shippingCountries', $salesChannel->getCountries()?->map(fn (CountryEntity $country) => $country->getIso()))
            ->withClaim('paypalMerchantId', $this->credentialsUtil->getMerchantPayerId($storefront->getId()))
            ->withClaim('shopwareMerchantId', $salesChannel->getId())
            ->withClaim('catalogDownloadUrl', sprintf('/%s/store-api/product-export/%s/%s', rtrim($url, '/'), $productExport->getAccessKey(), $productExport->getFileName()))
            ->getToken(new Sha256(), InMemory::plainText(random_bytes(32)))
            ->toString();

        try {
            $response = (new Client([
                'base_uri' => 'https://d.joinhoney.com/',
                'headers' => [
                    'Content-Type' => 'text/plain',
                ],
            ]))->post('webhooks/sw/install', ['body' => $token]);
        } catch (ClientException $e) {
            $response = $e->getResponse();
        }

        $source = $context->getSource();
        if (!$source instanceof AdminApiSource) {
            return;
        }

        $content = json_decode($response->getBody()->getContents(), true);

        $this->notificationService->createNotification(
            [
                'id' => Uuid::randomHex(),
                'status' => $content['success'] ? 'info' : 'error',
                'message' => 'PayPal agent commerce: ' . ($content['message'] ?? ''),
                'requiredPrivileges' => [],
                'createdByUserId' => $source->getUserId(),
            ],
            $context
        );
    }

    private function unregister(SalesChannelEntity $salesChannel, Context $context): void
    {
        // TODO: coming soon
    }
}
