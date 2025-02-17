<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\PUI\Service;

use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\DataBag;

#[Package('checkout')]
class PUICustomerDataService
{
    public const PUI_CUSTOMER_DATA_BIRTHDAY = 'payPalPuiCustomerBirthday';
    public const PUI_CUSTOMER_DATA_PHONE_NUMBER = 'payPalPuiCustomerPhoneNumber';

    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $orderAddressRepository,
        private readonly EntityRepository $customerRepository,
    ) {
    }

    public function checkForCustomerData(PaymentTransactionStruct $transaction, DataBag $dataBag, Context $context): void
    {
        $birthday = $this->getBirthday($dataBag);
        $phoneNumber = $dataBag->get(self::PUI_CUSTOMER_DATA_PHONE_NUMBER);

        if ($birthday) {
            $customerCriteria = new Criteria();
            $customerCriteria->addFilter(new EqualsFilter('orderCustomers.order.transactions.id', $transaction->getOrderTransactionId()));
            $customerId = $this->customerRepository->searchIds($customerCriteria, $context)->firstId();

            if ($customerId !== null) {
                $this->customerRepository->update([[
                    'id' => $customerId,
                    'birthday' => $birthday,
                ]], $context);
            }
        }

        if ($phoneNumber) {
            $addressCriteria = new Criteria();
            $addressCriteria->addFilter(new EqualsFilter('order.transactions.id', $transaction->getOrderTransactionId()));
            $billingAddressId = $this->orderAddressRepository->searchIds($addressCriteria, $context)->firstId();

            if ($billingAddressId !== null) {
                $this->orderAddressRepository->update([[
                    'id' => $billingAddressId,
                    'phoneNumber' => $phoneNumber,
                ]], $context);
            }
        }
    }

    private function getBirthday(DataBag $dataBag): ?\DateTimeInterface
    {
        $birthdayArray = $dataBag->get(self::PUI_CUSTOMER_DATA_BIRTHDAY);

        if (!($birthdayArray instanceof DataBag)) {
            return null;
        }

        $birthdayDay = $birthdayArray->getDigits('day');
        $birthdayMonth = $birthdayArray->getDigits('month');
        $birthdayYear = $birthdayArray->getDigits('year');

        if (!$birthdayDay || !$birthdayMonth || !$birthdayYear) {
            return null;
        }

        return new \DateTime(\sprintf(
            '%s-%s-%s',
            $birthdayYear,
            $birthdayMonth,
            $birthdayDay
        ));
    }
}
