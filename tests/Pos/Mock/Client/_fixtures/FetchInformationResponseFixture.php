<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Mock\Client\_fixtures;

use Shopware\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('checkout')]
class FetchInformationResponseFixture
{
    private const COUNTRY_CODE = 'DE';
    private const CURRENCY_CODE = 'EUR';
    private const LANGUAGE_CODE = 'de';

    public static function get(): array
    {
        return [
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
            'country' => self::COUNTRY_CODE,
            'language' => self::LANGUAGE_CODE,
            'currency' => self::CURRENCY_CODE,
            'created' => '2019-09-26T11:08:50.064+0000',
            'ownerUuid' => '049a25a4-e04e-11e9-805f-a3f6990e3d99',
            'organizationId' => 12312312,
            'customerStatus' => 'ACCEPTED',
            'usesVat' => true,
            'customerType' => 'NonLimitedCompany',
            'timeZone' => 'Europe/Berlin',
        ];
    }
}
