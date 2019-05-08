<?php declare(strict_types=1);

namespace Swag\PayPal\Util;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Swag\PayPal\Payment\PayPalPaymentHandler;

class PaymentMethodIdProvider
{
    /**
     * @var EntityRepositoryInterface
     */
    private $paymentRepository;

    public function __construct(EntityRepositoryInterface $paymentRepository)
    {
        $this->paymentRepository = $paymentRepository;
    }

    public function getPayPalPaymentMethodId(Context $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('handlerIdentifier', PayPalPaymentHandler::class));

        $result = $this->paymentRepository->searchIds($criteria, $context);
        if ($result->getTotal() === 0) {
            return null;
        }

        $paymentMethodIds = $result->getIds();

        return array_shift($paymentMethodIds);
    }
}
