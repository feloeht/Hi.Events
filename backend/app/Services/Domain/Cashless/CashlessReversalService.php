<?php

declare(strict_types=1);

namespace HiEvents\Services\Domain\Cashless;

use HiEvents\DomainObjects\CashlessTransactionDomainObject;
use HiEvents\Exceptions\CashlessTransactionNotReversibleException;
use HiEvents\Exceptions\CashlessWalletUnavailableException;
use HiEvents\Exceptions\InsufficientCashlessBalanceException;
use Throwable;

class CashlessReversalService
{
    public function __construct(
        private readonly CashlessWalletService $walletService,
        private readonly CashlessProductSalesService $productSalesService,
    ) {}

    /**
     * @throws CashlessTransactionNotReversibleException
     * @throws CashlessWalletUnavailableException
     * @throws InsufficientCashlessBalanceException
     * @throws Throwable
     */
    public function reverse(
        CashlessTransactionDomainObject $transaction,
        ?int $reversedByUserId,
        ?string $notes = null,
    ): CashlessTransactionDomainObject {
        $reversal = $this->walletService->reverse($transaction, $reversedByUserId, $notes);

        $this->productSalesService->unrecordSale($transaction);

        return $reversal;
    }
}
