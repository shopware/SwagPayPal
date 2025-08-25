<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Routing;

use Shopware\Core\Framework\Api\Context\ContextSource;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\JsonSerializableTrait;

/**
 * @internal
 */
#[Package('checkout')]
class AgentSource implements ContextSource, \JsonSerializable
{
    use JsonSerializableTrait;

    final public const SCOPE_CART = 'cart';
    final public const SCOPE_CHECKOUT = 'checkout';

    public string $type = AgentRouteScope::ID;

    private ?string $streamId = null;

    /**
     * @param string[] $scope
     */
    public function __construct(
        public readonly string $merchantId,
        public readonly \DateTimeInterface $issuedAt,
        public readonly \DateTimeInterface $expiresAt,
        public readonly array $scope,
        public readonly string $salesChannelId,
        public readonly ?string $debugId = null,
    ) {
    }

    public function hasScope(string $scope): bool
    {
        return \in_array($scope, $this->scope, true);
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTimeImmutable();
    }

    public function getStreamId(): ?string
    {
        return $this->streamId;
    }

    public function setStreamId(?string $streamId): void
    {
        $this->streamId = $streamId;
    }
}
