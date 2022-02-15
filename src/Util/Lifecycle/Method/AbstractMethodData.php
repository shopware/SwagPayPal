<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\Method;

use Shopware\Core\Framework\Context;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @psalm-consistent-constructor
 */
abstract class AbstractMethodData
{
    public const CAPABILITY_ACTIVE = 'active';
    public const CAPABILITY_INACTIVE = 'inactive';
    public const CAPABILITY_LIMITED = 'limited';

    protected ContainerInterface $container;

    final public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    abstract public function getTranslations(): array;

    abstract public function getPosition(): int;

    /**
     * @return class-string
     */
    abstract public function getHandler(): string;

    abstract public function getRuleData(Context $context): ?array;

    abstract public function getInitialState(): bool;

    abstract public function validateCapability(MerchantIntegrations $merchantIntegrations): string;
}
