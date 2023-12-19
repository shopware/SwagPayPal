<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PaymentSource;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\ExperienceContext;

/**
 * @OA\Schema(schema="swag_paypal_v2_order_payment_source_common")
 */
#[Package('checkout')]
abstract class AbstractPaymentSource extends PayPalApiStruct
{
    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_order_experience_context")
     */
    protected ExperienceContext $experienceContext;

    public function getExperienceContext(): ExperienceContext
    {
        return $this->experienceContext;
    }

    public function setExperienceContext(ExperienceContext $experienceContext): void
    {
        $this->experienceContext = $experienceContext;
    }
}
