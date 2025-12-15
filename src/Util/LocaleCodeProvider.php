<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Language\LanguageCollection;
use Shopware\Core\System\Language\LanguageEntity;
use Symfony\Contracts\Service\ResetInterface;

#[Package('checkout')]
class LocaleCodeProvider implements ResetInterface
{
    private const DEFAULT_LOCALE_CODE = 'en_GB';

    private EntityRepository $languageRepository;

    private LoggerInterface $logger;

    /**
     * @var array<string, string>
     */
    private array $cache = [];

    /**
     * @param EntityRepository<LanguageCollection> $languageRepository
     *
     * @internal
     */
    public function __construct(EntityRepository $languageRepository, LoggerInterface $logger)
    {
        $this->languageRepository = $languageRepository;
        $this->logger = $logger;
    }

    public function getLocaleCodeFromContext(Context $context): string
    {
        if (isset($this->cache[$context->getLanguageId()])) {
            return $this->cache[$context->getLanguageId()];
        }

        $languageId = $context->getLanguageId();
        $criteria = new Criteria([$languageId]);
        $criteria->addAssociation('locale');
        $criteria->setLimit(1);

        /** @var LanguageEntity $language */
        $language = $this->languageRepository->search($criteria, $context)->first();

        /** @var string $locale */
        $locale = $language->getLocale()?->getCode();

        return $this->cache[$context->getLanguageId()] = $locale;
    }

    public function getFormattedLocaleCode(string $localeCode): string
    {
        $canonicalizedCode = (string) \Locale::canonicalize($localeCode);

        $locales = SupportedLocales::LOCALES;

        if (!\in_array($canonicalizedCode, \array_merge(...\array_values($locales)), true)) {
            $matched = $this->findMatchingSupportedLocale($canonicalizedCode, $locales);
            if (!$matched) {
                $this->logger->notice(
                    \sprintf(
                        'PayPal does not support locale code %s. Switched to default %s.',
                        $localeCode,
                        self::DEFAULT_LOCALE_CODE
                    )
                );

                return self::DEFAULT_LOCALE_CODE;
            }

            $this->logger->notice(
                \sprintf(
                    'PayPal does not support locale code %s. Switched to %s.',
                    $localeCode,
                    $matched
                )
            );

            return $matched;
        }

        return $canonicalizedCode;
    }

    public function reset(): void
    {
        $this->cache = [];
    }

    private function findMatchingSupportedLocale(string $localeCode, array $locales): ?string
    {
        $localeCode = \Locale::getRegion($localeCode);

        return $locales[$localeCode][0] ?? null;
    }
}
