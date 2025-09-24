<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\AgentCommerce;

use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\AgentCommerce\HoneyWebhookService;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[Package('checkout')]
class HoneyWebhookServiceMock extends HoneyWebhookService
{
    /**
     * @param EntityRepository<SalesChannelCollection> $salesChannelRepository
     */
    public function __construct(
        EntityRepository $salesChannelRepository,
        CredentialsUtil $credentialsUtil,
        RouterInterface $router,
        SystemConfigService $systemConfigService,
        LoggerInterface $logger,
        ClientInterface $client
    ) {
        parent::__construct(
            $salesChannelRepository,
            $credentialsUtil,
            $router,
            $systemConfigService,
            $logger
        );

        $this->client = $client;
    }

    public static function create(
        EntityRepository $salesChannelRepository,
        CredentialsUtil $credentialsUtil,
        RouterInterface $router,
        SystemConfigService $systemConfigService,
        LoggerInterface $logger,
        ClientInterface $client
    ): self {
        return new self(
            $salesChannelRepository,
            $credentialsUtil,
            $router,
            $systemConfigService,
            $logger,
            $client
        );
    }
}
