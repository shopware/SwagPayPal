<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Setting\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\Currency\CurrencyEntity;
use Swag\PayPal\IZettle\Api\MerchantInformation;
use Swag\PayPal\IZettle\Resource\UserResource;
use Swag\PayPal\IZettle\Setting\Exception\CurrencyNotFoundException;
use Swag\PayPal\IZettle\Setting\Exception\IZettleInvalidApiCredentialsException;
use Swag\PayPal\IZettle\Setting\Struct\AdditionalInformation;

class InformationFetchService
{
    /**
     * @var UserResource
     */
    private $userResource;

    /**
     * @var EntityRepositoryInterface
     */
    private $currencyRepository;

    public function __construct(
        UserResource $userResource,
        EntityRepositoryInterface $currencyRepository
    ) {
        $this->userResource = $userResource;
        $this->currencyRepository = $currencyRepository;
    }

    public function fetchInformation(string $apiKey, Context $context): AdditionalInformation
    {
        $merchantInformation = $this->userResource->getMerchantInformation($apiKey);

        if (!$merchantInformation) {
            throw new IZettleInvalidApiCredentialsException();
        }

        $information = new AdditionalInformation();
        $information->setCurrencyId($this->getCurrencyId($merchantInformation, $context));

        return $information;
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
}
