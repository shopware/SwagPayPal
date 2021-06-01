<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @RouteScope(scopes={"storefront"})
 */
class ErrorController extends AbstractController
{
    private Session $session;

    private TranslatorInterface $translator;

    private LoggerInterface $logger;

    public function __construct(Session $session, TranslatorInterface $translator, LoggerInterface $logger)
    {
        $this->session = $session;
        $this->translator = $translator;
        $this->logger = $logger;
    }

    /**
     * @Route(
     *     "/paypal/add-error",
     *     name="payment.paypal.add_error",
     *     methods={"POST"},
     *     defaults={"XmlHttpRequest"=true}
     * )
     */
    public function addErrorMessage(Request $request): Response
    {
        $this->session->getFlashBag()->add('danger', $this->translator->trans('paypal.general.paymentError'));
        $this->logger->notice('Storefront checkout error', ['error' => $request->get('error')]);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
