<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Exception;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\JsonSerializableTrait;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Package('checkout')]
final class PayPalAgentErrorResponse extends JsonResponse implements \JsonSerializable
{
    use JsonSerializableTrait;

    /**
     * @param PayPalAgentErrorResponseDetail[] $details
     */
    public function __construct(
        public string $name,
        public int $code,
        public string $message,
        public ?string $debugId = null,
        public array $details = [],
        public ?\Throwable $previous = null,
    ) {
        $data = [
            'name' => $name,
            'message' => $message,
        ];

        if ($debugId !== null) {
            $data['debugId'] = $debugId;
        }

        if (!empty($details)) {
            $data['details'] = \array_map(
                static fn (PayPalAgentErrorResponseDetail $detail) => $detail->jsonSerialize(),
                $details,
            );
        }

        if ($previous !== null) {
            $data['previous'] = $previous;
        }

        parent::__construct($data, $code);
    }
}
