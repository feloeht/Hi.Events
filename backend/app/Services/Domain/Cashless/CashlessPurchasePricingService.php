<?php

declare(strict_types=1);

namespace HiEvents\Services\Domain\Cashless;

use HiEvents\DomainObjects\CashlessSalesPointDomainObject;
use HiEvents\DomainObjects\Enums\ProductType;
use HiEvents\DomainObjects\ProductDomainObject;
use HiEvents\DomainObjects\ProductPriceDomainObject;
use HiEvents\Helper\Currency;
use HiEvents\Services\Domain\Cashless\DTO\CashlessBasketDTO;
use HiEvents\Services\Domain\Cashless\DTO\CashlessPurchaseItemRequestDTO;
use HiEvents\Services\Domain\Cashless\DTO\CashlessTransactionItemDTO;
use HiEvents\Services\Domain\Tax\TaxAndFeeCalculationService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CashlessPurchasePricingService
{
    public function __construct(
        private readonly TaxAndFeeCalculationService $taxAndFeeCalculationService,
    ) {}

    /**
     * @param  Collection<CashlessPurchaseItemRequestDTO>  $requestedItems
     *
     * @throws ValidationException
     */
    public function priceBasket(
        CashlessSalesPointDomainObject $salesPoint,
        Collection $requestedItems,
    ): CashlessBasketDTO {
        $items = $requestedItems->map(
            fn (CashlessPurchaseItemRequestDTO $requestedItem) => $this->priceItem($salesPoint, $requestedItem)
        );

        return new CashlessBasketDTO(
            total: Currency::round($items->sum(fn (CashlessTransactionItemDTO $item) => $item->total)),
            items: $items,
        );
    }

    /**
     * @throws ValidationException
     */
    private function priceItem(
        CashlessSalesPointDomainObject $salesPoint,
        CashlessPurchaseItemRequestDTO $requestedItem,
    ): CashlessTransactionItemDTO {
        $product = $this->findSellableProduct($salesPoint, $requestedItem->product_id);
        $price = $this->findPrice($product, $requestedItem->product_price_id);

        $this->assertStockCovers($product, $price, $requestedItem->quantity);

        $taxesAndFees = $this->taxAndFeeCalculationService->calculateTaxAndFeesForProduct(
            product: $product,
            price: $price->getPrice(),
            quantity: $requestedItem->quantity,
        );

        $unitPrice = Currency::round($price->getPrice());
        $total = Currency::round(
            ($unitPrice * $requestedItem->quantity) + $taxesAndFees->taxTotal + $taxesAndFees->feeTotal
        );

        return new CashlessTransactionItemDTO(
            product_id: $product->getId(),
            product_price_id: $price->getId(),
            product_title: $this->itemTitle($product, $price),
            unit_price: $unitPrice,
            quantity: $requestedItem->quantity,
            total: $total,
        );
    }

    /**
     * @throws ValidationException
     */
    private function findSellableProduct(CashlessSalesPointDomainObject $salesPoint, int $productId): ProductDomainObject
    {
        $product = $salesPoint->getProducts()
            ?->first(fn (ProductDomainObject $product) => $product->getId() === $productId);

        if ($product === null) {
            throw ValidationException::withMessages([
                'items' => __('That product is not sold at this sales point.'),
            ]);
        }

        if ($product->getProductType() !== ProductType::GENERAL->name || $product->getIsCashlessTopup()) {
            throw ValidationException::withMessages([
                'items' => __('Only general products can be sold at a cashless sales point.'),
            ]);
        }

        return $product;
    }

    /**
     * @throws ValidationException
     */
    private function findPrice(ProductDomainObject $product, int $productPriceId): ProductPriceDomainObject
    {
        $price = $product->getProductPrices()
            ?->first(fn (ProductPriceDomainObject $price) => $price->getId() === $productPriceId);

        if ($price === null) {
            throw ValidationException::withMessages([
                'items' => __('That price is no longer available. Please reload the sales point.'),
            ]);
        }

        return $price;
    }

    /**
     * @throws ValidationException
     */
    private function assertStockCovers(ProductDomainObject $product, ProductPriceDomainObject $price, int $quantity): void
    {
        if ($price->isSoldOut()) {
            throw ValidationException::withMessages([
                'items' => __(':product is sold out.', ['product' => $product->getTitle()]),
            ]);
        }

        $remaining = $this->remainingQuantity($price);

        if ($remaining !== null && $quantity > $remaining) {
            throw ValidationException::withMessages([
                'items' => __('Only :count left of :product.', [
                    'count' => $remaining,
                    'product' => $product->getTitle(),
                ]),
            ]);
        }
    }

    private function remainingQuantity(ProductPriceDomainObject $price): ?int
    {
        if ($price->getQuantityAvailable() !== null) {
            return $price->getQuantityAvailable();
        }

        if ($price->getInitialQuantityAvailable() === null) {
            return null;
        }

        return max(0, $price->getInitialQuantityAvailable() - $price->getQuantitySold());
    }

    private function itemTitle(ProductDomainObject $product, ProductPriceDomainObject $price): string
    {
        return $price->getLabel()
            ? sprintf('%s - %s', $product->getTitle(), $price->getLabel())
            : $product->getTitle();
    }
}
