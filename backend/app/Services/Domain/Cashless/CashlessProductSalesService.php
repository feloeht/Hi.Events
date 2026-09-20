<?php

declare(strict_types=1);

namespace HiEvents\Services\Domain\Cashless;

use HiEvents\DomainObjects\CashlessTransactionDomainObject;
use HiEvents\DomainObjects\CashlessTransactionItemDomainObject;
use HiEvents\DomainObjects\Enums\CashlessTransactionType;
use HiEvents\DomainObjects\Generated\CashlessTransactionItemDomainObjectAbstract;
use HiEvents\Repository\Interfaces\CashlessTransactionItemRepositoryInterface;
use HiEvents\Services\Domain\Cashless\DTO\CashlessTransactionItemDTO;
use HiEvents\Services\Domain\Product\ProductQuantityUpdateService;
use Illuminate\Support\Collection;

class CashlessProductSalesService
{
    public function __construct(
        private readonly ProductQuantityUpdateService $productQuantityUpdateService,
        private readonly CashlessTransactionItemRepositoryInterface $transactionItemRepository,
    ) {}

    /**
     * @param  Collection<CashlessTransactionItemDTO>  $items
     */
    public function recordSale(Collection $items): void
    {
        $items->each(fn (CashlessTransactionItemDTO $item) => $this->productQuantityUpdateService->increaseQuantitySold(
            priceId: $item->product_price_id,
            adjustment: $item->quantity,
        ));
    }

    public function unrecordSale(CashlessTransactionDomainObject $transaction): void
    {
        if ($transaction->getType() !== CashlessTransactionType::PURCHASE->value) {
            return;
        }

        $this->transactionItemRepository
            ->findWhere([
                CashlessTransactionItemDomainObjectAbstract::CASHLESS_TRANSACTION_ID => $transaction->getId(),
            ])
            ->each(fn (CashlessTransactionItemDomainObject $item) => $this->productQuantityUpdateService->decreaseQuantitySold(
                priceId: $item->getProductPriceId(),
                adjustment: $item->getQuantity(),
            ));
    }
}
