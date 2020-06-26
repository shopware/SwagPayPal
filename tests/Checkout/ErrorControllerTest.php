<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Swag\PayPal\Checkout\ErrorController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

class ErrorControllerTest extends TestCase
{
    use KernelTestBehaviour;

    public function testAddErrorMessage(): void
    {
        $container = $this->getContainer();
        /** @var Session $session */
        $session = $container->get('session');
        /** @var TranslatorInterface $translator */
        $translator = $container->get('translator');

        $dangerFlashes = $session->getFlashBag()->get('danger');
        static::assertCount(0, $dangerFlashes);

        $response = (new ErrorController($session, $translator))->addErrorMessage();

        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $dangerFlashes = $session->getFlashBag()->get('danger');
        static::assertCount(1, $dangerFlashes);
    }
}
