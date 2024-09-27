<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Administration;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\RoutingException;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['api']])]
class PayPalPaymentMethodController extends AbstractController
{
    /**
     * @internal
     */
    public function __construct(
        private readonly PaymentMethodUtil $paymentMethodUtil,
    ) {
    }

    /**
     * @phpstan-ignore-next-line
     */
    #[OA\Post(
        path: '/api/_action/paypal/saleschannel-default',
        operationId: 'setPayPalAsDefault',
        description: 'Sets PayPal as the default payment method for a given Saleschannel, or all.',
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [new OA\Property(
            property: 'salesChannelId',
            description: 'The id of the Saleschannel where PayPal should be set as the default payment method. Set to null to set PayPal as default for every Saleschannel.',
            type: 'string',
            nullable: true
        )])),
        tags: ['Admin Api', 'SwagPayPalPaymentMethod'],
        responses: [new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Setting PayPal as default was successful')],
    )]
    #[Route(path: '/api/_action/paypal/saleschannel-default', name: 'api.action.paypal.saleschannel_default', defaults: ['_acl' => ['swag_paypal.editor']], methods: ['POST'])]
    public function setPayPalPaymentMethodAsSalesChannelDefault(Request $request, Context $context): Response
    {
        $salesChannelId = $request->request->get('salesChannelId');
        if ($salesChannelId !== null && !\is_string($salesChannelId)) {
            throw RoutingException::invalidRequestParameter('salesChannelId');
        }

        $this->paymentMethodUtil->setPayPalAsDefaultPaymentMethod($context, $salesChannelId);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
