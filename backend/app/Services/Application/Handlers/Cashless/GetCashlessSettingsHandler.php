<?php

declare(strict_types=1);

namespace HiEvents\Services\Application\Handlers\Cashless;

use HiEvents\DomainObjects\EventSettingDomainObject;
use HiEvents\DomainObjects\Generated\EventSettingDomainObjectAbstract;
use HiEvents\Exceptions\ResourceNotFoundException;
use HiEvents\Repository\Interfaces\EventSettingsRepositoryInterface;

class GetCashlessSettingsHandler
{
    public function __construct(
        private readonly EventSettingsRepositoryInterface $eventSettingsRepository,
    ) {}

    /**
     * @throws ResourceNotFoundException
     */
    public function handle(int $eventId): EventSettingDomainObject
    {
        $settings = $this->eventSettingsRepository->findFirstWhere([
            EventSettingDomainObjectAbstract::EVENT_ID => $eventId,
        ]);

        if ($settings === null) {
            throw new ResourceNotFoundException(
                __('Settings for event :id could not be found.', ['id' => $eventId])
            );
        }

        return $settings;
    }
}
