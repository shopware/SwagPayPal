<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Routing;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\AbstractRouteScope;
use Shopware\Core\PlatformRequest;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
class AgentRouteScope extends AbstractRouteScope implements AgentContextRouteScopeDependant
{
    final public const ATTRIBUTE_PAYPAL_AGENT_SCOPE = '_agentScope';
    final public const ID = 'paypal-agent';

    protected array $allowedPaths = ['api'];

    public function isAllowed(Request $request): bool
    {
        if (!$request->headers->has('Authorization') || !$request->headers->has('Content-Type')) {
            return false;
        }

        if ($request->headers->get('Content-Type') !== 'application/json') {
            return false;
        }

        $context = $request->attributes->get(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT);

        if (!$context instanceof Context) {
            return false;
        }

        $source = $context->getSource();

        if (!$source instanceof AgentSource) {
            return false;
        }

        return true;
    }

    public function getId(): string
    {
        return self::ID;
    }
}
