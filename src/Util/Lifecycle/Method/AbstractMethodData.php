<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\Method;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;
use Swag\PayPal\Util\Availability\AvailabilityContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @internal
 */
#[Package('checkout')]
abstract class AbstractMethodData
{
    public const CAPABILITY_ACTIVE = 'active';
    public const CAPABILITY_INACTIVE = 'inactive';
    public const CAPABILITY_INELIGIBLE = 'ineligible';
    public const CAPABILITY_LIMITED = 'limited';

    protected ContainerInterface $container;

    /**
     * @internal
     */
    final public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return array<string, array<string, string>>
     */
    abstract public function getTranslations(): array;

    abstract public function getPosition(): int;

    abstract public function getHandler(): string;

    abstract public function getTechnicalName(): string;

    abstract public function isAvailable(AvailabilityContext $availabilityContext): bool;

    abstract public function getInitialState(): bool;

    abstract public function validateCapability(MerchantIntegrations $merchantIntegrations): string;

    abstract public function getMediaFileName(): ?string;

    public function isVaultable(SalesChannelContext $context): bool
    {
        return false;
    }
}
