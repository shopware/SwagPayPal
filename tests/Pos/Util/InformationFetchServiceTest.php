<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Util;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Swag\PayPal\Pos\Api\PosRequestUri;
use Swag\PayPal\Pos\Client\PosClient;
use Swag\PayPal\Pos\Client\PosClientFactory;
use Swag\PayPal\Pos\Resource\UserResource;
use Swag\PayPal\Pos\Setting\Exception\CountryNotFoundException;
use Swag\PayPal\Pos\Setting\Exception\CurrencyNotFoundException;
use Swag\PayPal\Pos\Setting\Service\InformationFetchService;
use Swag\PayPal\Pos\Setting\Struct\AdditionalInformation;

/**
 * @internal
 */
#[Package('checkout')]
class InformationFetchServiceTest extends TestCase
{
    use KernelTestBehaviour;

    private const COUNTRY_CODE = 'DE';
    private const CURRENCY_CODE = 'EUR';
    private const LANGUAGE_CODE = 'de';
    private const INVALID_COUNTRY_CODE = 'XY';
    private const AMBIGUOUS_COUNTRY_CODE = 'AT';
    private const INVALID_CURRENCY_CODE = 'XYZ';
    private const INVALID_LANGUAGE_CODE = 'xy';
    private const TEST_API_KEY = 'testApiKey';

    public function testInformation(): void
    {
        $context = Context::createDefaultContext();
        $informationFetchService = $this->getInformationFetchService(
            $this->createUserResource()
        );

        $information = new AdditionalInformation();
        $informationFetchService->addInformation($information, self::TEST_API_KEY, $context);

        $expected = $this->createExpected(self::COUNTRY_CODE, $context);

        static::assertEquals($expected, $information);
    }

    public function testInvalidCountry(): void
    {
        $context = Context::createDefaultContext();
        $informationFetchService = $this->getInformationFetchService(
            $this->createUserResource(self::COUNTRY_CODE, self::INVALID_CURRENCY_CODE)
        );

        $this->expectException(CurrencyNotFoundException::class);
        $informationFetchService->addInformation(new AdditionalInformation(), self::TEST_API_KEY, $context);
    }

    public function testInvalidCurrency(): void
    {
        $context = Context::createDefaultContext();
        $informationFetchService = $this->getInformationFetchService(
            $this->createUserResource(self::INVALID_COUNTRY_CODE)
        );

        $this->expectException(CountryNotFoundException::class);
        $informationFetchService->addInformation(new AdditionalInformation(), self::TEST_API_KEY, $context);
    }

    public function testInvalidLanguage(): void
    {
        $context = Context::createDefaultContext();
        $informationFetchService = $this->getInformationFetchService(
            $this->createUserResource(self::COUNTRY_CODE, self::CURRENCY_CODE, self::INVALID_LANGUAGE_CODE)
        );
        $information = new AdditionalInformation();
        $informationFetchService->addInformation($information, self::TEST_API_KEY, $context);

        $expected = $this->createExpected(self::COUNTRY_CODE, $context);
        $expected->setLanguageId(null);

        static::assertEquals($expected, $information);
    }

    public function testAmbiguousLanguageCountry(): void
    {
        $context = Context::createDefaultContext();
        $informationFetchService = $this->getInformationFetchService(
            $this->createUserResource(self::AMBIGUOUS_COUNTRY_CODE)
        );

        $information = new AdditionalInformation();
        $informationFetchService->addInformation($information, self::TEST_API_KEY, $context);

        $expected = $this->createExpected(self::AMBIGUOUS_COUNTRY_CODE, $context);

        static::assertEquals($expected, $information);
    }

    private function createUserResource(
        string $countryCode = self::COUNTRY_CODE,
        string $currencyCode = self::CURRENCY_CODE,
        string $languageCode = self::LANGUAGE_CODE
    ): UserResource {
        $client = $this->createMock(PosClient::class);
        $client->expects(static::once())->method('sendGetRequest')
            ->with(PosRequestUri::MERCHANT_INFORMATION)
            ->willReturn([
                'uuid' => '0497dde4-e04e-11e9-81af-0fbace9c2068',
                'name' => 'Max Mustermann',
                'receiptName' => 'MAX MUSTERMANN',
                'city' => 'MUSTERSTADT',
                'zipCode' => '12345',
                'address' => 'HAUPTSTR. 1',
                'addressLine2' => '',
                'legalName' => 'MAX MUSTERMANN',
                'legalAddress' => 'HAUPTSTR. 1',
                'legalZipCode' => '12345',
                'legalCity' => 'MUSTERSTADT',
                'legalState' => '',
                'phoneNumber' => '+491771111111',
                'contactEmail' => 'someone@somewhere.com',
                'receiptEmail' => 'someone@somewhere.com',
                'legalEntityType' => 'COMPANY',
                'legalEntityNr' => 'xyz123123',
                'vatPercentage' => 19.0,
                'country' => $countryCode,
                'language' => $languageCode,
                'currency' => $currencyCode,
                'created' => '2019-09-26T11:08:50.064+0000',
                'ownerUuid' => '049a25a4-e04e-11e9-805f-a3f6990e3d99',
                'organizationId' => 12312312,
                'customerStatus' => 'ACCEPTED',
                'usesVat' => true,
                'customerType' => 'NonLimitedCompany',
                'timeZone' => 'Europe/Berlin',
            ]);

        $clientFactory = $this->createMock(PosClientFactory::class);
        $clientFactory->method('getPosClient')->willReturn($client);

        return new UserResource($clientFactory);
    }

    private function getInformationFetchService(UserResource $userResource): InformationFetchService
    {
        /** @var EntityRepository $countryRepository */
        $countryRepository = $this->getContainer()->get('country.repository');
        /** @var EntityRepository $currencyRepository */
        $currencyRepository = $this->getContainer()->get('currency.repository');
        /** @var EntityRepository $languageRepository */
        $languageRepository = $this->getContainer()->get('language.repository');

        return new InformationFetchService(
            $userResource,
            $countryRepository,
            $currencyRepository,
            $languageRepository
        );
    }

    private function createExpected(string $countryCode, Context $context): AdditionalInformation
    {
        $countryCriteria = new Criteria();
        $countryCriteria->addFilter(new EqualsFilter('iso', $countryCode));
        /** @var EntityRepository $countryRepository */
        $countryRepository = $this->getContainer()->get('country.repository');

        $languageCriteria = new Criteria();
        $languageCriteria->addFilter(new EqualsFilter('name', 'Deutsch'));
        /** @var EntityRepository $languageRepository */
        $languageRepository = $this->getContainer()->get('language.repository');

        $expected = new AdditionalInformation();
        $expected->assign([
            'countryId' => $countryRepository->searchIds($countryCriteria, $context)->firstId(),
            'currencyId' => Defaults::CURRENCY,
            'languageId' => $languageRepository->searchIds($languageCriteria, $context)->firstId(),
            'merchantInformation' => [
                'name' => 'Max Mustermann',
                'contactEmail' => 'someone@somewhere.com',
            ],
        ]);

        return $expected;
    }
}
