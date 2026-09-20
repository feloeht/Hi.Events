<?php

declare(strict_types=1);

namespace HiEvents\Resources\Cashless;

use HiEvents\DomainObjects\EventSettingDomainObject;
use HiEvents\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin EventSettingDomainObject
 */
class CashlessSettingsResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'event_id' => $this->getEventId(),
            'cashless_enabled' => $this->getCashlessEnabled(),
            'cashless_topup_product_id' => $this->getCashlessTopupProductId(),
            'cashless_min_topup_amount' => $this->getCashlessMinTopupAmount(),
            'cashless_allow_remaining_balance_refund' => $this->getCashlessAllowRemainingBalanceRefund(),
            'cashless_refund_deadline_at' => $this->getCashlessRefundDeadlineAt(),
        ];
    }
}
