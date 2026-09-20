<?php

declare(strict_types=1);

namespace HiEvents\Services\Application\Handlers\Cashless\Wallet;

use HiEvents\DomainObjects\CashlessWalletDomainObject;
use HiEvents\DomainObjects\Generated\CashlessWalletDomainObjectAbstract;
use HiEvents\DomainObjects\Status\CashlessWalletStatus;
use HiEvents\Exceptions\ResourceNotFoundException;
use HiEvents\Repository\Interfaces\CashlessWalletRepositoryInterface;

class UpdateCashlessWalletStatusHandler
{
    public function __construct(
        private readonly CashlessWalletRepositoryInterface $walletRepository,
        private readonly GetCashlessWalletHandler $getCashlessWalletHandler,
    ) {}

    /**
     * @throws ResourceNotFoundException
     */
    public function handle(int $eventId, int $walletId, CashlessWalletStatus $status): CashlessWalletDomainObject
    {
        $this->getCashlessWalletHandler->handle($eventId, $walletId);

        $this->walletRepository->updateWhere(
            attributes: [CashlessWalletDomainObjectAbstract::STATUS => $status->value],
            where: [
                CashlessWalletDomainObjectAbstract::ID => $walletId,
                CashlessWalletDomainObjectAbstract::EVENT_ID => $eventId,
            ],
        );

        return $this->getCashlessWalletHandler->handle($eventId, $walletId);
    }
}
