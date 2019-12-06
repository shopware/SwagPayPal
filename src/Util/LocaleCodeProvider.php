<?php declare(strict_types=1);

namespace Swag\PayPal\Util;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Language\LanguageCollection;

class LocaleCodeProvider
{
    /**
     * @var EntityRepositoryInterface
     */
    private $languageRepository;

    public function __construct(EntityRepositoryInterface $languageRepository)
    {
        $this->languageRepository = $languageRepository;
    }

    /**
     * @throws InconsistentCriteriaIdsException
     */
    public function getLocaleCodeFromContext(Context $context): string
    {
        $languageId = $context->getLanguageId();
        $criteria = new Criteria([$languageId]);
        $criteria->addAssociation('locale');
        /** @var LanguageCollection $languageCollection */
        $languageCollection = $this->languageRepository->search($criteria, $context)->getEntities();

        $language = $languageCollection->get($languageId);
        if ($language === null) {
            return 'en-GB';
        }

        $locale = $language->getLocale();
        if (!$locale) {
            return 'en-GB';
        }

        return $locale->getCode();
    }
}
