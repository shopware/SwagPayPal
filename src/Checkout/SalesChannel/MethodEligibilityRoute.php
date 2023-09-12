<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SalesChannel;

use OpenApi\Annotations as OA;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Routing\Exception\InvalidRequestParameterException;
use Shopware\Core\Framework\Routing\RoutingException;
use Shopware\Core\System\SalesChannel\NoContentResponse;
use Swag\PayPal\Checkout\Payment\Method\ACDCHandler;
use Swag\PayPal\Checkout\Payment\Method\PayLaterHandler;
use Swag\PayPal\Checkout\Payment\Method\SEPAHandler;
use Swag\PayPal\Checkout\Payment\Method\VenmoHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"store-api"}})
 */
#[Package('checkout')]
class MethodEligibilityRoute extends AbstractMethodEligibilityRoute
{
    public const REMOVABLE_PAYMENT_HANDLERS = [
        'CARD' => ACDCHandler::class,
        'SEPA' => SEPAHandler::class,
        'VENMO' => VenmoHandler::class,
        'PAYLATER' => PayLaterHandler::class,
    ];

    public const SESSION_KEY = 'payPalIneligiblePaymentMethods';

    private LoggerInterface $logger;

    /**
     * @internal
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getDecorated(): AbstractMethodEligibilityRoute
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * @OA\Post(
     *     path="/store-api/paypal/payment-method-eligibility",
     *     description="Sets ineligible payment methods to be removed from the session",
     *     operationId="setPaymentMethodEligibility",
     *     tags={"Store API", "PayPal"},
     *
     *     @OA\RequestBody(
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="paymentMethods",
     *                 type="array",
     *
     *                 @OA\Items(
     *                     type="string",
     *                 ),
     *                 description="List of PayPal payment method identifiers according to constant REMOVABLE_PAYMENT_HANDLERS"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *          response="204"
     *     )
     * )
     *
     * @Route(
     *     "/store-api/paypal/payment-method-eligibility",
     *     name="store-api.paypal.payment-method-eligibility",
     *     methods={"POST"},
     *     defaults={"XmlHttpRequest"=true}
     * )
     */
    public function setPaymentMethodEligibility(Request $request, Context $context): Response
    {
        /** @var mixed|array $paymentMethods */
        $paymentMethods = $request->request->all()['paymentMethods'] ?? null;
        if (!\is_array($paymentMethods)) {
            if (\class_exists(RoutingException::class)) {
                RoutingException::invalidRequestParameter('paymentMethods');
            } else {
                /** @phpstan-ignore-next-line remove condition and keep if branch with min-version 6.5.2.0 */
                throw new InvalidRequestParameterException('paymentMethods');
            }
        }

        $handlers = [];
        foreach ($paymentMethods as $paymentMethod) {
            if (self::REMOVABLE_PAYMENT_HANDLERS[$paymentMethod] ?? null) {
                $handlers[] = self::REMOVABLE_PAYMENT_HANDLERS[$paymentMethod];
            }
        }

        $request->getSession()->set(self::SESSION_KEY, $handlers);
        $this->logger->info('Removed ineligible PayPal payment methods from session', ['handlers' => $handlers]);

        return new NoContentResponse();
    }
}
