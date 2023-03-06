<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\SalesChannel;

use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Swag\PayPal\Checkout\SalesChannel\ErrorRoute;
use Swag\PayPal\Test\Mock\LoggerMock;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class ErrorRouteTest extends TestCase
{
    use KernelTestBehaviour;

    public function testAddErrorMessage(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $request = new Request([], ['error' => 'test']);
        $request->setSession($session);
        $logger = new LoggerMock();

        $flashes = $session->getFlashBag()->all();
        static::assertCount(0, $flashes);

        $response = $this->callErrorRoute($request, $logger);

        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        static::assertCount(1, $session->getFlashBag()->get('danger'));

        static::assertCount(1, $logger->getLogs());
        static::assertSame(Logger::NOTICE, \current($logger->getLogs())['level']);
        static::assertArrayHasKey('error', \current($logger->getLogs())['context']);
        static::assertSame('test', \current($logger->getLogs())['context']['error']);
    }

    public function testAddCancelMessage(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $request = new Request([], ['cancel' => 'true']);
        $request->setSession($session);
        $logger = new LoggerMock();

        $flashes = $session->getFlashBag()->all();
        static::assertCount(0, $flashes);

        $response = $this->callErrorRoute($request, $logger);

        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        static::assertCount(1, $session->getFlashBag()->get('danger'));

        static::assertCount(1, $logger->getLogs());
        static::assertSame(Logger::NOTICE, \current($logger->getLogs())['level']);
    }

    private function callErrorRoute(Request $request, LoggerInterface $logger): Response
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->getContainer()->get('translator');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        return (new ErrorRoute($requestStack, $translator, $logger))->addErrorMessage($request);
    }
}
