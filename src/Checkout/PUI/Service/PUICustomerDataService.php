<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\PUI\Service;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
class PUICustomerDataService
{
    public const PUI_CUSTOMER_DATA_BIRTHDAY = 'payPalPuiCustomerBirthday';
    public const PUI_CUSTOMER_DATA_PHONE_NUMBER = 'payPalPuiCustomerPhoneNumber';

    private EntityRepository $orderAddressRepository;

    private EntityRepository $customerRepository;

    /**
     * @internal
     */
    public function __construct(
        EntityRepository $orderAddressRepository,
        EntityRepository $customerRepository,
    ) {
        $this->orderAddressRepository = $orderAddressRepository;
        $this->customerRepository = $customerRepository;
    }

    public function checkForCustomerData(OrderEntity $order, DataBag $dataBag, SalesChannelContext $salesChannelContext): void
    {
        $birthday = $this->getBirthday($dataBag);
        $phoneNumber = $dataBag->get(self::PUI_CUSTOMER_DATA_PHONE_NUMBER);
        $customer = $salesChannelContext->getCustomer();

        if ($birthday && $customer !== null) {
            $this->customerRepository->update([[
                'id' => $customer->getId(),
                'birthday' => $birthday,
            ]], $salesChannelContext->getContext());

            $customer->setBirthday($birthday);
        }

        if ($phoneNumber) {
            $this->orderAddressRepository->update([[
                'id' => $order->getBillingAddressId(),
                'phoneNumber' => $phoneNumber,
            ]], $salesChannelContext->getContext());

            $billingAddress = $order->getBillingAddress();
            if ($billingAddress !== null) {
                $billingAddress->setPhoneNumber($phoneNumber);
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
