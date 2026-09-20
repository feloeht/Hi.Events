<?php

declare(strict_types=1);

namespace HiEvents\Services\Domain\Cashless;

use Carbon\Carbon;
use HiEvents\DomainObjects\EventSettingDomainObject;
use HiEvents\DomainObjects\Generated\EventSettingDomainObjectAbstract;
use HiEvents\Exceptions\CashlessNotEnabledException;
use HiEvents\Repository\Interfaces\EventSettingsRepositoryInterface;

class CashlessSettingsService
{
    public function __construct(
        private readonly EventSettingsRepositoryInterface $eventSettingsRepository,
    ) {}

    /**
     * @throws CashlessNotEnabledException
     */
    public function getEnabledSettings(int $eventId): EventSettingDomainObject
    {
        $settings = $this->eventSettingsRepository->findFirstWhere([
            EventSettingDomainObjectAbstract::EVENT_ID => $eventId,
        ]);

        if ($settings === null || ! $settings->getCashlessEnabled()) {
            throw new CashlessNotEnabledException(
                __('Cashless payments are not enabled for this event.')
            );
        }

        return $settings;
    }

    public function isRefundWindowOpen(EventSettingDomainObject $settings): bool
    {
        if (! $settings->getCashlessAllowRemainingBalanceRefund()) {
            return false;
        }

        $deadline = $settings->getCashlessRefundDeadlineAt();

        return $deadline === null || Carbon::parse($deadline)->isFuture();
    }
}
