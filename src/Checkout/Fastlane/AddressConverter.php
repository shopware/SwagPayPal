<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Fastlane;

use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\ChangeSetAware;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Checkout\Order\Shipping\MessageQueue\ShippingInformationMessage;
use Swag\PayPal\Checkout\SalesChannel\Struct\Common\Address;
use Swag\PayPal\Checkout\SalesChannel\Struct\Common\Name;
use Swag\PayPal\Checkout\SalesChannel\Struct\Common\PhoneNumber;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
#[Package('checkout')]
class AddressConverter
{
    public function __construct(
        private readonly EntityRepository $countryRepository,
        private readonly EntityRepository $countryStateRepository,
        private readonly EntityRepository $salutationRepository,
    ) {
    }

    /**
     * @return array<string, string|null>
     */
    public function convertAddressData(Address $address, Name $name, ?PhoneNumber $phoneNumber, Context $context, ?string $salutationId = null): array
    {
        $countryCode = $address->getCountryCode();
        $countryId = $this->getCountryId($countryCode, $context);
        $countryStateId = $this->getCountryStateId($countryId, $countryCode, $address->getAdminArea1(), $context);

        return [
            'firstName' => $name->getFirstName(),
            'lastName' => $name->getLastName(),
            'salutationId' => $salutationId ?? $this->getSalutationId($context),
            'street' => $address->getAddressLine1(),
            'zipcode' => $address->getPostalCode(),
            'countryId' => $countryId,
            'countryStateId' => $countryStateId,
            'phoneNumber' => $phoneNumber?->getNationalNumber(),
            'city' => $address->getAdminArea2(),
            'additionalAddressLine1' => $address->getAddressLine2(),
        ];
    }

    public function getSalutationId(Context $context): string
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addFilter(new EqualsFilter('salutationKey', 'not_specified'));

        $salutationId = $this->salutationRepository->searchIds($criteria, $context)->firstId();

        if ($salutationId !== null) {
            return $salutationId;
        }

        $salutationId = $this->salutationRepository->searchIds($criteria->resetFilters(), $context)->firstId();

        if ($salutationId === null) {
            throw new \RuntimeException('No salutation found in Shopware');
        }

        return $salutationId;
    }

    private function getCountryId(string $code, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', $code));

        return $this->countryRepository->searchIds($criteria, $context)->firstId();
    }

    private function getCountryStateId(?string $countryId, string $countryCode, ?string $stateCode, Context $context): ?string
    {
        if ($countryId === null) {
            return null;
        }

        if ($stateCode === null || $stateCode === '') {
            return null;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('countryId', $countryId));
        $criteria->addFilter(new EqualsFilter('shortCode', \sprintf('%s-%s', $countryCode, $stateCode)));

        return $this->countryStateRepository->searchIds($criteria, $context)->firstId();
    }
}
