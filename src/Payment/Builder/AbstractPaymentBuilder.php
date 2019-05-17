<?php declare(strict_types=1);

namespace Swag\PayPal\Payment\Builder;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Language\LanguageCollection;
use Shopware\Core\Framework\Language\LanguageEntity;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\PayPal\Api\Payment\ApplicationContext;
use Swag\PayPal\PayPal\Api\Payment\Payer;
use Swag\PayPal\PayPal\Api\Payment\RedirectUrls;
use Swag\PayPal\PayPal\Api\Payment\Transaction\Amount;
use Swag\PayPal\PayPal\Api\Payment\Transaction\Amount\Details;
use Swag\PayPal\PayPal\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\PayPal\PaymentIntent;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Setting\SwagPayPalSettingGeneralStruct;

abstract class AbstractPaymentBuilder
{
    /**
     * @var SettingsServiceInterface
     */
    protected $settingsService;

    /**
     * @var SwagPayPalSettingGeneralStruct
     */
    protected $settings;

    /**
     * @var EntityRepositoryInterface
     */
    protected $languageRepo;

    /**
     * @var EntityRepositoryInterface
     */
    protected $salesChannelRepo;

    public function __construct(
        SettingsServiceInterface $settingsService,
        EntityRepositoryInterface $languageRepo,
        EntityRepositoryInterface $salesChannelRepo
    ) {
        $this->settingsService = $settingsService;
        $this->languageRepo = $languageRepo;
        $this->salesChannelRepo = $salesChannelRepo;
    }

    /**
     * @throws PayPalSettingsInvalidException
     */
    protected function getIntent(): string
    {
        $intent = $this->settings->getIntent();
        $this->validateIntent($intent);

        return $intent;
    }

    protected function createPayer(): Payer
    {
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');

        return $payer;
    }

    protected function createRedirectUrls(string $returnUrl): RedirectUrls
    {
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setCancelUrl(sprintf('%s&cancel=1', $returnUrl));
        $redirectUrls->setReturnUrl($returnUrl);

        return $redirectUrls;
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    protected function getApplicationContext(SalesChannelContext $salesChannelContext): ApplicationContext
    {
        $applicationContext = new ApplicationContext();
        $applicationContext->setLocale($this->getLocaleCode($salesChannelContext->getContext()));
        $applicationContext->setBrandName($this->getBrandName($salesChannelContext));
        $applicationContext->setLandingPage($this->getLandingPageType());

        return $applicationContext;
    }

    protected function createAmount(
        CalculatedPrice $transactionAmount,
        float $shippingCostsTotal,
        string $currency
    ): Amount {
        $amount = new Amount();
        $amount->setTotal($this->formatPrice($transactionAmount->getTotalPrice()));
        $amount->setCurrency($currency);
        $amount->setDetails($this->getAmountDetails($shippingCostsTotal, $transactionAmount));

        return $amount;
    }

    protected function formatPrice(float $price): string
    {
        return (string) round($price, 2);
    }

    /**
     * @throws PayPalSettingsInvalidException
     */
    private function validateIntent(string $intent): void
    {
        if (!\in_array($intent, PaymentIntent::INTENTS, true)) {
            throw new PayPalSettingsInvalidException('intent');
        }
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    private function getLocaleCode(Context $context): string
    {
        $languageId = $context->getLanguageId();
        /** @var LanguageCollection $languageCollection */
        $languageCollection = $this->languageRepo->search(new Criteria([$languageId]), $context);
        /** @var LanguageEntity $language */
        $language = $languageCollection->get($languageId);

        return $language->getLocale()->getCode();
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    private function getBrandName(SalesChannelContext $salesChannelContext): string
    {
        $brandName = $this->settings->getBrandName();

        if ($brandName === null || $brandName === '') {
            $brandName = $this->useSalesChannelNameAsBrandName($salesChannelContext);
        }

        return $brandName;
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    private function useSalesChannelNameAsBrandName(SalesChannelContext $salesChannelContext): string
    {
        $salesChannelId = $salesChannelContext->getSalesChannel()->getId();
        /** @var SalesChannelCollection $salesChannelCollection */
        $salesChannelCollection = $this->salesChannelRepo->search(
            new Criteria([$salesChannelId]),
            $salesChannelContext->getContext()
        );
        $salesChannel = $salesChannelCollection->get($salesChannelId);

        $brandName = '';
        if ($salesChannel !== null) {
            $salesChannelName = $salesChannel->getName();
            if ($salesChannelName !== null) {
                $brandName = $salesChannelName;
            }
        }

        return $brandName;
    }

    private function getLandingPageType(): string
    {
        $landingPageType = $this->settings->getLandingPage();
        if ($landingPageType !== ApplicationContext::LANDINGPAGE_TYPE_BILLING) {
            $landingPageType = ApplicationContext::LANDINGPAGE_TYPE_LOGIN;
        }

        return $landingPageType;
    }

    private function getAmountDetails(float $shippingCostsTotal, CalculatedPrice $orderTransactionAmount): Details
    {
        $amountDetails = new Details();

        $amountDetails->setShipping($this->formatPrice($shippingCostsTotal));
        $totalAmount = $orderTransactionAmount->getTotalPrice();
        $taxAmount = $orderTransactionAmount->getCalculatedTaxes()->getAmount();
        $amountDetails->setSubtotal($this->formatPrice($totalAmount - $taxAmount));
        $amountDetails->setTax($this->formatPrice($taxAmount));

        return $amountDetails;
    }
}
