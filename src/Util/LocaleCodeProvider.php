<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Language\LanguageEntity;
use Symfony\Contracts\Service\ResetInterface;

#[Package('checkout')]
class LocaleCodeProvider implements ResetInterface
{
    private EntityRepository $languageRepository;

    private array $cache = [];

    /**
     * @internal
     */
    public function __construct(EntityRepository $languageRepository)
    {
        $this->languageRepository = $languageRepository;
    }

    public function getLocaleCodeFromContext(Context $context): string
    {
        if ($this->cache[$context->getLanguageId()] ?? null) {
            return $this->cache[$context->getLanguageId()];
        }

        $languageId = $context->getLanguageId();
        $criteria = new Criteria([$languageId]);
        $criteria->addAssociation('locale');
        $criteria->setLimit(1);
        /** @var LanguageEntity|null $language */
        $language = $this->languageRepository->search($criteria, $context)->first();
        if ($language === null) {
            return $this->cache[$context->getLanguageId()] = 'en-GB';
        }

        $locale = $language->getLocale();
        if (!$locale) {
            return $this->cache[$context->getLanguageId()] = 'en-GB';
        }

        return $this->cache[$context->getLanguageId()] = $locale->getCode();
    }

    public function reset(): void
    {
        $this->cache = [];
    }
}
