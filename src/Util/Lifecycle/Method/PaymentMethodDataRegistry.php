<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle\Method;

use Shopware\Core\Checkout\Payment\Exception\UnknownPaymentMethodException;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PaymentMethodDataRegistry
{
    /**
     * Required, because container can't deliver tagged services during plugin install
     *
     * @var array<class-string<AbstractMethodData>>
     */
    private const PAYMENT_METHODS = [
        PayPalMethodData::class,
        PUIMethodData::class,
        ACDCMethodData::class,
        SEPAMethodData::class,
        BancontactMethodData::class,
        BlikMethodData::class,
        //BoletoBancarioMethodData::class,
        EpsMethodData::class,
        IdealMethodData::class,
        MultibancoMethodData::class,
        MyBankMethodData::class,
        OxxoMethodData::class,
        P24MethodData::class,
        TrustlyMethodData::class,
        VenmoMethodData::class,
        PayLaterMethodData::class,
    ];

    private EntityRepository $paymentMethodRepository;

    private ContainerInterface $container;

    private ?iterable $paymentMethods;

    /**
     * @internal
     */
    public function __construct(
        EntityRepository $paymentMethodRepository,
        ContainerInterface $container,
        ?iterable $paymentMethods = null
    ) {
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->container = $container;
        $this->paymentMethods = $paymentMethods;
    }

    public function getEntityIdFromData(AbstractMethodData $method, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('handlerIdentifier', $method->getHandler()));

        return $this->paymentMethodRepository->searchIds($criteria, $context)->firstId();
    }

    public function getEntityFromData(AbstractMethodData $method, Context $context): ?PaymentMethodEntity
    {
        $criteria = new Criteria();
        $criteria->addAssociation('availabilityRule');
        $criteria->addFilter(new EqualsFilter('handlerIdentifier', $method->getHandler()));

        return $this->paymentMethodRepository->search($criteria, $context)->first();
    }

    /**
     * @return AbstractMethodData[]
     */
    public function getPaymentMethods(): array
    {
        if ($this->paymentMethods !== null) {
            if (!\is_array($this->paymentMethods)) {
                $this->paymentMethods = [...$this->paymentMethods];
            }

            return $this->paymentMethods;
        }

        $methods = [];
        foreach (self::PAYMENT_METHODS as $methodDataClass) {
            /** @var AbstractMethodData $method */
            $method = new $methodDataClass($this->container);
            $methods[] = $method;
        }

        $this->paymentMethods = $methods;

        return $methods;
    }

    /**
     * @param class-string<AbstractMethodData> $methodDataClass
     */
    public function getPaymentMethod(string $methodDataClass): AbstractMethodData
    {
        if ($this->paymentMethods === null) {
            if (!\class_exists($methodDataClass)) {
                throw new UnknownPaymentMethodException($methodDataClass);
            }

            return new $methodDataClass($this->container);
        }

        foreach ($this->paymentMethods as $paymentMethod) {
            if ($paymentMethod instanceof $methodDataClass) {
                return $paymentMethod;
            }
        }

        throw new UnknownPaymentMethodException($methodDataClass);
    }

    public function getPaymentMethodByHandler(string $paymentHandler): ?AbstractMethodData
    {
        $paymentMethods = $this->getPaymentMethods();

        foreach ($paymentMethods as $paymentMethod) {
            if ($paymentMethod->getHandler() === $paymentHandler) {
                return $paymentMethod;
            }
        }

        return null;
    }

    public function isPayPalPaymentMethod(PaymentMethodEntity $paymentMethod): bool
    {
        foreach ($this->getPaymentMethods() as $methodData) {
            if ($paymentMethod->getHandlerIdentifier() === $methodData->getHandler()) {
                return true;
            }
        }

        return false;
    }
}
