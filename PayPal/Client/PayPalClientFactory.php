<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Client;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use SwagPayPal\PayPal\Client\Exception\PayPalSettingsInvalidException;
use SwagPayPal\PayPal\Resource\TokenResource;
use SwagPayPal\Setting\SwagPayPalSettingGeneralCollection;

class PayPalClientFactory
{
    /**
     * @var TokenResource
     */
    private $tokenResource;

    /**
     * @var RepositoryInterface
     */
    private $settingGeneralRepo;

    public function __construct(TokenResource $tokenResource, RepositoryInterface $settingGeneralRepo)
    {
        $this->tokenResource = $tokenResource;
        $this->settingGeneralRepo = $settingGeneralRepo;
    }

    /**
     * @throws PayPalSettingsInvalidException
     */
    public function createPaymentClient(Context $context): PayPalClient
    {
        /** @var SwagPayPalSettingGeneralCollection $settingsCollection */
        $settingsCollection = $this->settingGeneralRepo->search(new Criteria(), $context)->getEntities();
        if ($settingsCollection->count() === 0) {
            throw new PayPalSettingsInvalidException('');
        }
        $settings = $settingsCollection->first();

        return new PayPalClient($this->tokenResource, $context, $settings);
    }
}
