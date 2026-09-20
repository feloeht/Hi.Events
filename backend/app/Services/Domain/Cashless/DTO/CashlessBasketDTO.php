<?php

namespace HiEvents\Services\Domain\Cashless\DTO;

use HiEvents\DataTransferObjects\BaseDataObject;
use Illuminate\Support\Collection;

class CashlessBasketDTO extends BaseDataObject
{
    public function __construct(
        public float $total,
        /** @var Collection<CashlessTransactionItemDTO> */
        public Collection $items,
    ) {}
}
