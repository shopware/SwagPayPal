<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout;

use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Swag\PayPal\Checkout\SalesChannel\AbstractErrorRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @deprecated tag:v4.0.0 - will be removed. Use Swag\PayPal\Checkout\SalesChannel\ErrorRoute instead
 *
 * @RouteScope(scopes={"storefront"})
 */
class ErrorController extends AbstractController
{
    private AbstractErrorRoute $errorRoute;

    public function __construct(AbstractErrorRoute $errorRoute)
    {
        $this->errorRoute = $errorRoute;
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
        return $this->errorRoute->addErrorMessage($request);
    }
}
