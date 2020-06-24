<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Util;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Swag\PayPal\IZettle\Api\IZettleRequestUri;
use Swag\PayPal\IZettle\Client\IZettleClient;
use Swag\PayPal\IZettle\Client\IZettleClientFactory;
use Swag\PayPal\IZettle\Resource\UserResource;
use Swag\PayPal\IZettle\Setting\Exception\CurrencyNotFoundException;
use Swag\PayPal\IZettle\Setting\Service\InformationFetchService;
use Swag\PayPal\IZettle\Setting\Struct\AdditionalInformation;

class InformationFetchServiceTest extends TestCase
{
    use KernelTestBehaviour;

    private const CURRENCY_CODE = 'EUR';
    private const INVALID_CURRENCY_CODE = 'XYZ';
    private const TEST_API_KEY = 'testApiKey';

    public function testInformation(): void
    {
        $context = Context::createDefaultContext();

        /** @var EntityRepositoryInterface $currencyRepository */
        $currencyRepository = $this->getContainer()->get('currency.repository');

        $informationFetchService = new InformationFetchService(
            $this->createUserResource(self::CURRENCY_CODE),
            $currencyRepository
        );

        $information = $informationFetchService->fetchInformation(self::TEST_API_KEY, $context);

        $expected = new AdditionalInformation();
        $expected->assign([
            'currencyId' => Defaults::CURRENCY,
        ]);

        static::assertEquals($expected, $information);
    }

    public function testInvalidCurrency(): void
    {
        $context = Context::createDefaultContext();

        /** @var EntityRepositoryInterface $currencyRepository */
        $currencyRepository = $this->getContainer()->get('currency.repository');

        $informationFetchService = new InformationFetchService(
            $this->createUserResource(self::INVALID_CURRENCY_CODE),
            $currencyRepository
        );

        $this->expectException(CurrencyNotFoundException::class);
        $informationFetchService->fetchInformation(self::TEST_API_KEY, $context);
    }

    private function createUserResource(string $currencyCode): UserResource
    {
        $client = $this->createMock(IZettleClient::class);
        $client->expects(static::once())->method('sendGetRequest')
            ->with(IZettleRequestUri::MERCHANT_INFORMATION)
            ->willReturn([
                'uuid' => '0497dde4-e04e-11e9-81af-0fbace9c2068',
                'name' => 'Julian Dreißig',
                'receiptName' => 'JULIAN DREISSIG',
                'city' => 'BERLIN',
                'zipCode' => '10823',
                'address' => 'HAUPTSTR. 1',
                'addressLine2' => '',
                'legalName' => 'JULIAN DREISSIG',
                'legalAddress' => 'HAUPTSTR. 1',
                'legalZipCode' => '10823',
                'legalCity' => 'BERLIN',
                'legalState' => '',
                'phoneNumber' => '+491771111111',
                'contactEmail' => 'jdreissig@paypal.com',
                'receiptEmail' => 'jdreissig@paypal.com',
                'legalEntityType' => 'COMPANY',
                'legalEntityNr' => 'xyz123123',
                'vatPercentage' => 19.0,
                'country' => 'DE',
                'language' => 'de',
                'currency' => $currencyCode,
                'created' => '2019-09-26T11:08:50.064+0000',
                'ownerUuid' => '049a25a4-e04e-11e9-805f-a3f6990e3d99',
                'organizationId' => 12312312,
                'customerStatus' => 'ACCEPTED',
                'usesVat' => true,
                'customerType' => 'NonLimitedCompany',
                'timeZone' => 'Europe/Berlin',
            ]);

        $clientFactory = $this->createMock(IZettleClientFactory::class);
        $clientFactory->method('createIZettleClient')->willReturn($client);

        return new UserResource($clientFactory);
    }
}
