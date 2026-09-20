<?php

namespace HiEvents\Services\Application\Handlers\Cashless\DTO;

use HiEvents\DataTransferObjects\BaseDataObject;

class CreateCashlessTopupDTO extends BaseDataObject
{
    public function __construct(
        public int $event_id,
        public string $attendee_short_id,
        public float $amount,
        public string $session_identifier,
        public bool $is_user_authenticated = false,
        public ?string $order_locale = null,
    ) {}
}
