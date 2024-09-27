<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Setting\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\Language\LanguageEntity;
use Swag\PayPal\Pos\Api\MerchantInformation;
use Swag\PayPal\Pos\Resource\UserResource;
use Swag\PayPal\Pos\Setting\Exception\CountryNotFoundException;
use Swag\PayPal\Pos\Setting\Exception\CurrencyNotFoundException;
use Swag\PayPal\Pos\Setting\Exception\PosInvalidApiCredentialsException;
use Swag\PayPal\Pos\Setting\Struct\AdditionalInformation;

#[Package('checkout')]
class InformationFetchService
{
    private UserResource $userResource;

    private EntityRepository $countryRepository;

    private EntityRepository $currencyRepository;

    private EntityRepository $languageRepository;

    /**
     * @internal
     */
    public function __construct(
        UserResource $userResource,
        EntityRepository $countryRepository,
        EntityRepository $currencyRepository,
        EntityRepository $languageRepository,
    ) {
        $this->userResource = $userResource;
        $this->countryRepository = $countryRepository;
        $this->currencyRepository = $currencyRepository;
        $this->languageRepository = $languageRepository;
    }

    public function addInformation(
        AdditionalInformation $information,
        string $apiKey,
        Context $context,
    ): void {
        $merchantInformation = $this->userResource->getMerchantInformation($apiKey);

        if (!$merchantInformation) {
            throw new PosInvalidApiCredentialsException();
        }

        $information->setCountryId($this->getCountryId($merchantInformation, $context));
        $information->setCurrencyId($this->getCurrencyId($merchantInformation, $context));
        $information->setLanguageId($this->getLanguageId($merchantInformation, $context));
        $information->setMerchantInformation($this->getMerchantInformation($merchantInformation));
    }

    private function getMerchantInformation(MerchantInformation $merchantInformation): array
    {
        return [
            'name' => $merchantInformation->getName(),
            'contactEmail' => $merchantInformation->getContactEmail(),
        ];
    }

    private function getCountryId(MerchantInformation $merchantInformation, Context $context): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', $merchantInformation->getCountry()));

        /** @var CountryEntity|null $country */
        $country = $this->countryRepository->search($criteria, $context)->first();

        if ($country === null) {
            throw new CountryNotFoundException($merchantInformation->getCountry());
        }

        return $country->getId();
    }

    private function getCurrencyId(MerchantInformation $merchantInformation, Context $context): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('isoCode', $merchantInformation->getCurrency()));

        /** @var CurrencyEntity|null $currency */
        $currency = $this->currencyRepository->search($criteria, $context)->first();

        if ($currency === null) {
            throw new CurrencyNotFoundException($merchantInformation->getCurrency());
        }

        return $currency->getId();
    }

    private function getLanguageId(MerchantInformation $merchantInformation, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('locale.code', $merchantInformation->getLanguage() . '-' . $merchantInformation->getCountry()));

        /** @var LanguageEntity|null $language */
        $language = $this->languageRepository->search($criteria, $context)->first();

        if ($language !== null) {
            return $language->getId();
        }

        $criteria = new Criteria();
        $criteria->addFilter(new ContainsFilter('locale.code', $merchantInformation->getLanguage() . '-'));

        /** @var LanguageEntity|null $language */
        $language = $this->languageRepository->search($criteria, $context)->first();

        if ($language === null) {
            return null;
        }

        return $language->getId();
    }
}
