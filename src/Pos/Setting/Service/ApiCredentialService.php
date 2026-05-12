<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Setting\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\Api\Authentication\OAuthCredentials;
use Swag\PayPal\Pos\Api\Service\ApiKeyDecoder;
use Swag\PayPal\Pos\Resource\TokenResource;
use Swag\PayPal\Pos\Util\PosSalesChannelTrait;
use Swag\PayPal\SwagPayPal;

#[Package('checkout')]
class ApiCredentialService
{
    use PosSalesChannelTrait;

    private TokenResource $tokenResource;

    private EntityRepository $salesChannelRepository;

    private ApiKeyDecoder $apiKeyDecoder;

    /**
     * @internal
     */
    public function __construct(
        TokenResource $tokenResource,
        EntityRepository $salesChannelRepository,
        ApiKeyDecoder $apiKeyDecoder,
    ) {
        $this->tokenResource = $tokenResource;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->apiKeyDecoder = $apiKeyDecoder;
    }

    public function testApiCredentials(string $apiKey): bool
    {
        $credentials = new OAuthCredentials();
        $credentials->setApiKey($apiKey);

        return $this->tokenResource->testApiCredentials($credentials);
    }

    /**
     * @return array<string, string>
     */
    public function checkForDuplicates(string $apiKey, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('typeId', SwagPayPal::SALES_CHANNEL_TYPE_POS));
        $criteria->addAssociation(SwagPayPal::SALES_CHANNEL_POS_EXTENSION);
        $salesChannels = $this->salesChannelRepository->search($criteria, $context)->getEntities();

        $newUuid = $this->apiKeyDecoder->decode($apiKey)->getPayload()->getUser()->getUuid();

        $duplicates = [];

        /** @var SalesChannelEntity $salesChannel */
        foreach ($salesChannels as $salesChannel) {
            $posSalesChannel = $this->getPosSalesChannel($salesChannel);
            $existingUuid = $this->apiKeyDecoder->decode($posSalesChannel->getApiKey())->getPayload()->getUser()->getUuid();

            if ($existingUuid === $newUuid) {
                $duplicates[$salesChannel->getId()] = $salesChannel->getName() ?? $salesChannel->getId();
            }
        }

        return $duplicates;
    }
}
