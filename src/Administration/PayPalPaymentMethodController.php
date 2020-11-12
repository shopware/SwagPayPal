<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Administration;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\Acl;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 */
class PayPalPaymentMethodController extends AbstractController
{
    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    public function __construct(PaymentMethodUtil $paymentMethodUtil)
    {
        $this->paymentMethodUtil = $paymentMethodUtil;
    }

    /**
     * @OA\Post(
     *     path="/_action/paypal/saleschannel-default",
     *     description="Sets PayPal as the default payment method for a given Saleschannel, or all.",
     *     operationId="setPayPalAsDefault",
     *     tags={"Admin Api", "SwagPayPalPaymentMethod"},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="salesChannelId",
     *                 description="The id of the Saleschannel where PayPal should be set as the default payment method. Set to null to set PayPal as default for every Saleschannel.",
     *                 type="string"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response="204"
     *     )
     * )
     *
     * @Route("/api/v{version}/_action/paypal/saleschannel-default", name="api.action.paypal.saleschannel_default", methods={"POST"})
     * @Acl({"swag_paypal.editor"})
     */
    public function setPayPalPaymentMethodAsSalesChannelDefault(Request $request, Context $context): Response
    {
        $this->paymentMethodUtil->setPayPalAsDefaultPaymentMethod(
            $context,
            $request->request->get('salesChannelId')
        );

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
