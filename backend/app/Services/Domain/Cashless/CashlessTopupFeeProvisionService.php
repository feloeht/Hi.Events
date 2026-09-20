<?php

declare(strict_types=1);

namespace HiEvents\Services\Domain\Cashless;

use HiEvents\DomainObjects\Enums\TaxCalculationType;
use HiEvents\DomainObjects\Enums\TaxType;
use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\DomainObjects\EventSettingDomainObject;
use HiEvents\DomainObjects\Generated\EventSettingDomainObjectAbstract;
use HiEvents\DomainObjects\Generated\TaxAndFeesDomainObjectAbstract;
use HiEvents\DomainObjects\TaxAndFeesDomainObject;
use HiEvents\Repository\Interfaces\EventSettingsRepositoryInterface;
use HiEvents\Repository\Interfaces\ProductRepositoryInterface;
use HiEvents\Repository\Interfaces\TaxAndFeeRepositoryInterface;

class CashlessTopupFeeProvisionService
{
    public function __construct(
        private readonly TaxAndFeeRepositoryInterface $taxAndFeeRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly EventSettingsRepositoryInterface $eventSettingsRepository,
    ) {}

    public function sync(
        EventDomainObject $event,
        EventSettingDomainObject $settings,
        float $fixedFee,
        float $percentageFee,
    ): void {
        $fixedFeeId = $this->syncFee(
            existingFeeId: $settings->getCashlessTopupFixedFeeId(),
            accountId: $event->getAccountId(),
            name: __('Cashless top-up fee'),
            calculationType: TaxCalculationType::FIXED,
            rate: $fixedFee,
        );

        $percentageFeeId = $this->syncFee(
            existingFeeId: $settings->getCashlessTopupPercentageFeeId(),
            accountId: $event->getAccountId(),
            name: __('Cashless top-up percentage fee'),
            calculationType: TaxCalculationType::PERCENTAGE,
            rate: $percentageFee,
        );

        $this->eventSettingsRepository->updateWhere(
            attributes: [
                EventSettingDomainObjectAbstract::CASHLESS_TOPUP_FIXED_FEE_ID => $fixedFeeId,
                EventSettingDomainObjectAbstract::CASHLESS_TOPUP_PERCENTAGE_FEE_ID => $percentageFeeId,
            ],
            where: [EventSettingDomainObjectAbstract::EVENT_ID => $event->getId()],
        );

        $this->attachToTopupProduct(
            $settings->getCashlessTopupProductId(),
            array_values(array_filter([$fixedFeeId, $percentageFeeId])),
        );
    }

    private function syncFee(
        ?int $existingFeeId,
        int $accountId,
        string $name,
        TaxCalculationType $calculationType,
        float $rate,
    ): ?int {
        if ($existingFeeId !== null) {
            $this->taxAndFeeRepository->updateWhere(
                attributes: [
                    TaxAndFeesDomainObjectAbstract::RATE => $rate,
                    TaxAndFeesDomainObjectAbstract::IS_ACTIVE => $rate > 0,
                ],
                where: [TaxAndFeesDomainObjectAbstract::ID => $existingFeeId],
            );

            return $rate > 0 ? $existingFeeId : null;
        }

        if ($rate <= 0) {
            return null;
        }

        /** @var TaxAndFeesDomainObject $fee */
        $fee = $this->taxAndFeeRepository->create([
            TaxAndFeesDomainObjectAbstract::NAME => $name,
            TaxAndFeesDomainObjectAbstract::CALCULATION_TYPE => $calculationType->name,
            TaxAndFeesDomainObjectAbstract::RATE => $rate,
            TaxAndFeesDomainObjectAbstract::IS_ACTIVE => true,
            TaxAndFeesDomainObjectAbstract::IS_DEFAULT => false,
            TaxAndFeesDomainObjectAbstract::ACCOUNT_ID => $accountId,
            TaxAndFeesDomainObjectAbstract::TYPE => TaxType::FEE->name,
        ]);

        return $fee->getId();
    }

    private function attachToTopupProduct(?int $topupProductId, array $feeIds): void
    {
        if ($topupProductId === null) {
            return;
        }

        $this->productRepository->addTaxesAndFeesToProduct($topupProductId, $feeIds);
    }
}
