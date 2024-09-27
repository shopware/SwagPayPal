<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\State;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Util\Lifecycle\Method\AbstractMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;

/**
 * @internal
 */
#[Package('checkout')]
class PaymentMethodStateService
{
    private PaymentMethodDataRegistry $methodDataRegistry;

    private EntityRepository $paymentMethodRepository;

    /**
     * @internal
     */
    public function __construct(
        PaymentMethodDataRegistry $methodDataRegistry,
        EntityRepository $paymentMethodRepository,
    ) {
        $this->methodDataRegistry = $methodDataRegistry;
        $this->paymentMethodRepository = $paymentMethodRepository;
    }

    /**
     * @param class-string<AbstractMethodData> $methodDataClass
     */
    public function setPaymentMethodState(string $methodDataClass, bool $active, Context $context): void
    {
        $method = $this->methodDataRegistry->getPaymentMethod($methodDataClass);

        $this->setPaymentMethodStateByHandler($method->getHandler(), $active, $context);
    }

    public function setPaymentMethodStateByHandler(string $handler, bool $active, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('handlerIdentifier', $handler));

        /** @var string|null $id */
        $id = $this->paymentMethodRepository->searchIds($criteria, $context)->firstId();
        if ($id === null) {
            return;
        }

        $this->setPaymentMethodStateById($id, $active, $context);
    }

    public function setPaymentMethodStateById(string $id, bool $active, Context $context): void
    {
        $this->paymentMethodRepository->update([[
            'id' => $id,
            'active' => $active,
        ]], $context);
    }

    public function setAllPaymentMethodsState(bool $active, Context $context): void
    {
        $handlers = [];
        foreach ($this->methodDataRegistry->getPaymentMethods() as $paymentMethod) {
            if (!$active || $paymentMethod->getInitialState()) {
                $handlers[] = $paymentMethod->getHandler();
            }
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('handlerIdentifier', $handlers));
        /** @var string[] $ids */
        $ids = $this->paymentMethodRepository->searchIds($criteria, $context)->getIds();

        if (!$ids) {
            return;
        }

        $this->paymentMethodRepository->update(\array_map(static function (string $id) use ($active) {
            return [
                'id' => $id,
                'active' => $active,
            ];
        }, $ids), $context);
    }
}
