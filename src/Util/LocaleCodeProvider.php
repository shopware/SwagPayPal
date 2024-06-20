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
    private EntityRepository $languageRepository;

    private LoggerInterface $logger;

    /**
     * @var array<string, string>
     */
    private array $cache = [];

    private const SUPPORTED_LOCALE_CODE_LENGTH = 5;

    private const DEFAULT_LOCALE_CODE = 'en_GB';

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

        if (\mb_strlen($canonicalizedCode) !== self::SUPPORTED_LOCALE_CODE_LENGTH) {
            $this->logger->notice(
                \sprintf(
                    'PayPal does not support locale code %s. Switch to default %s',
                    $localeCode,
                    self::DEFAULT_LOCALE_CODE
                )
            );

            return self::DEFAULT_LOCALE_CODE;
        }

        return $canonicalizedCode;
    }

    public function reset(): void
    {
        $this->cache = [];
    }
}
