<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Routing;

use Shopware\Core\Framework\Api\Context\AdminSalesChannelApiSource;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\AbstractRouteScope;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
class AgentRouteScope extends AbstractRouteScope
{
    final public const ATTRIBUTE_PAYPAL_AGENT_SCOPE = '_agentScope';
    final public const ID = 'paypal-agent';

    /**
     * @var array<string>
     *
     * @deprecated tag:v10.0.0 - Will be natively typed
     */
    protected $allowedPaths = ['api']; // @phpstan-ignore shopware.propertyNativeType

    public function isAllowed(Request $request): bool
    {
        if (!$request->headers->has('Authorization')) {
            return false;
        }

        $context = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);

        if (!$context instanceof SalesChannelContext) {
            return false;
        }

        $source = $context->getContext()->getSource();

        if ($source instanceof AgentSource) {
            return true;
        }

        if ($source instanceof AdminSalesChannelApiSource && $source->getOriginalContext()->getSource() instanceof AgentSource) {
            return true;
        }

        return false;
    }

    public function getId(): string
    {
        return self::ID;
    }
}
