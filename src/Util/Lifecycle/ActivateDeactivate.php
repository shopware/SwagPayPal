<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Util\PaymentMethodUtil;

class ActivateDeactivate
{
    /**
     * @var EntityRepositoryInterface
     */
    private $paymentRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $customFieldRepository;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    public function __construct(
        PaymentMethodUtil $paymentMethodUtil,
        EntityRepositoryInterface $paymentRepository,
        EntityRepositoryInterface $customFieldRepository
    ) {
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->paymentRepository = $paymentRepository;
        $this->customFieldRepository = $customFieldRepository;
    }

    public function activate(Context $context): void
    {
        $this->setPaymentMethodsIsActive(true, $context);
        $this->activateOrderTransactionCustomField($context);
    }

    public function deactivate(Context $context): void
    {
        $this->setPaymentMethodsIsActive(false, $context);
        $this->deactivateOrderTransactionCustomField($context);
    }

    private function setPaymentMethodsIsActive(bool $active, Context $context): void
    {
        $payPalPaymentMethodId = $this->paymentMethodUtil->getPayPalPaymentMethodId($context);

        if ($payPalPaymentMethodId === null) {
            return;
        }

        $updateData = [[
            'id' => $payPalPaymentMethodId,
            'active' => $active,
        ]];

        $this->paymentRepository->update($updateData, $context);
    }

    private function activateOrderTransactionCustomField(Context $context): void
    {
        $customFieldIds = $this->getCustomFieldIds($context);

        if ($customFieldIds->getTotal() !== 0) {
            return;
        }

        $this->customFieldRepository->upsert(
            [
                [
                    'name' => SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_TRANSACTION_ID,
                    'type' => CustomFieldTypes::TEXT,
                ],
            ],
            $context
        );
    }

    private function deactivateOrderTransactionCustomField(Context $context): void
    {
        $customFieldIds = $this->getCustomFieldIds($context);

        if ($customFieldIds->getTotal() === 0) {
            return;
        }

        $ids = \array_map(static function ($id) {
            return ['id' => $id];
        }, $customFieldIds->getIds());
        $this->customFieldRepository->delete($ids, $context);
    }

    private function getCustomFieldIds(Context $context): IdSearchResult
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_TRANSACTION_ID));

        return $this->customFieldRepository->searchIds($criteria, $context);
    }
}
