<?php

declare(strict_types=1);

namespace HiEvents\Services\Application\Handlers\Cashless;

use HiEvents\DomainObjects\Generated\TaxAndFeesDomainObjectAbstract;
use HiEvents\Exceptions\ResourceNotFoundException;
use HiEvents\Repository\Interfaces\TaxAndFeeRepositoryInterface;
use HiEvents\Services\Application\Handlers\Cashless\DTO\CashlessSettingsDTO;
use HiEvents\Services\Domain\Cashless\CashlessSettingsService;

class GetCashlessSettingsHandler
{
    public function __construct(
        private readonly CashlessSettingsService $cashlessSettingsService,
        private readonly TaxAndFeeRepositoryInterface $taxAndFeeRepository,
    ) {}

    /**
     * @throws ResourceNotFoundException
     */
    public function handle(int $eventId): CashlessSettingsDTO
    {
        $settings = $this->cashlessSettingsService->getSettings($eventId);

        return new CashlessSettingsDTO(
            event_id: $eventId,
            cashless_enabled: $settings->getCashlessEnabled(),
            cashless_topup_product_id: $settings->getCashlessTopupProductId(),
            cashless_min_topup_amount: $settings->getCashlessMinTopupAmount(),
            cashless_allow_remaining_balance_refund: $settings->getCashlessAllowRemainingBalanceRefund(),
            cashless_refund_deadline_at: $settings->getCashlessRefundDeadlineAt(),
            cashless_online_topup_enabled: $settings->getCashlessOnlineTopupEnabled(),
            cashless_topup_fixed_fee: $this->feeRate($settings->getCashlessTopupFixedFeeId()),
            cashless_topup_percentage_fee: $this->feeRate($settings->getCashlessTopupPercentageFeeId()),
        );
    }

    private function feeRate(?int $feeId): float
    {
        if ($feeId === null) {
            return 0.0;
        }

        return $this->taxAndFeeRepository->findFirstWhere([
            TaxAndFeesDomainObjectAbstract::ID => $feeId,
        ])?->getRate() ?? 0.0;
    }
}
