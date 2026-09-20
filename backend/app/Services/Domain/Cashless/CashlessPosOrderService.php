<?php

declare(strict_types=1);

namespace HiEvents\Services\Domain\Cashless;

use HiEvents\DomainObjects\AttendeeDomainObject;
use HiEvents\DomainObjects\Enums\PaymentProviders;
use HiEvents\DomainObjects\Generated\OrderDomainObjectAbstract;
use HiEvents\DomainObjects\OrderDomainObject;
use HiEvents\DomainObjects\OrderItemDomainObject;
use HiEvents\DomainObjects\Status\OrderPaymentStatus;
use HiEvents\DomainObjects\Status\OrderStatus;
use HiEvents\Repository\Interfaces\OrderRepositoryInterface;
use HiEvents\Services\Application\Handlers\Order\CreateOrderHandler;
use HiEvents\Services\Application\Handlers\Order\DTO\CreateOrderPublicDTO;
use HiEvents\Services\Application\Handlers\Order\DTO\ProductOrderDetailsDTO;
use HiEvents\Services\Domain\Cashless\DTO\CashlessPurchaseItemRequestDTO;
use HiEvents\Services\Domain\EventStatistics\EventStatisticsIncrementService;
use HiEvents\Services\Domain\Order\OrderCancelService;
use HiEvents\Services\Domain\Product\DTO\OrderProductPriceDTO;
use HiEvents\Services\Domain\Product\ProductQuantityUpdateService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

class CashlessPosOrderService
{
    public function __construct(
        private readonly CreateOrderHandler $createOrderHandler,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ProductQuantityUpdateService $productQuantityUpdateService,
        private readonly EventStatisticsIncrementService $statisticsIncrementService,
        private readonly OrderCancelService $orderCancelService,
    ) {}

    /**
     * @param  Collection<CashlessPurchaseItemRequestDTO>  $items
     *
     * @throws Throwable
     */
    public function recordSale(
        int $eventId,
        AttendeeDomainObject $attendee,
        Collection $items,
        string $locale,
    ): OrderDomainObject {
        $order = $this->createOrderHandler->handle(
            eventId: $eventId,
            createOrderPublicDTO: CreateOrderPublicDTO::fromArray([
                'is_user_authenticated' => true,
                'session_identifier' => Str::random(40),
                'order_locale' => $locale,
                'products' => $items->map(fn (CashlessPurchaseItemRequestDTO $item) => new ProductOrderDetailsDTO(
                    product_id: $item->product_id,
                    quantities: new Collection([
                        new OrderProductPriceDTO(
                            quantity: $item->quantity,
                            price_id: $item->product_price_id,
                        ),
                    ]),
                )),
            ]),
            deleteExistingOrdersForSession: false,
        );

        $completedOrder = $this->orderRepository
            ->loadRelation(OrderItemDomainObject::class)
            ->updateFromArray($order->getId(), [
                OrderDomainObjectAbstract::FIRST_NAME => $attendee->getFirstName(),
                OrderDomainObjectAbstract::LAST_NAME => $attendee->getLastName(),
                OrderDomainObjectAbstract::EMAIL => $attendee->getEmail(),
                OrderDomainObjectAbstract::STATUS => OrderStatus::COMPLETED->name,
                OrderDomainObjectAbstract::PAYMENT_STATUS => OrderPaymentStatus::PAYMENT_RECEIVED->name,
                OrderDomainObjectAbstract::PAYMENT_PROVIDER => PaymentProviders::CASHLESS->value,
                OrderDomainObjectAbstract::RESERVED_UNTIL => null,
            ]);

        $this->productQuantityUpdateService->updateQuantitiesFromOrder($completedOrder);
        $this->statisticsIncrementService->incrementForOrder($completedOrder);

        return $completedOrder;
    }

    /**
     * @throws Throwable
     */
    public function cancelSale(int $orderId): void
    {
        $order = $this->orderRepository
            ->loadRelation(OrderItemDomainObject::class)
            ->findById($orderId);

        $this->orderCancelService->cancelOrder($order);
    }
}
