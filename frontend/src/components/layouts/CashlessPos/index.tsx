import {t} from "@lingui/macro";
import {Tabs} from "@mantine/core";
import {IconArrowsExchange, IconCoin, IconReceipt} from "@tabler/icons-react";
import {useState} from "react";
import {useParams} from "react-router";
import {CashlessStaffPaymentMethod, CashlessTransaction, CashlessWalletPublic, Product} from "../../../types.ts";
import {publicCashlessClient} from "../../../api/cashless-public.client.ts";
import {useGetCashlessSalesPointPublic} from "../../../queries/useGetCashlessSalesPointPublic.ts";
import {HomepageInfoMessage} from "../../common/HomepageInfoMessage";
import {showError, showSuccess} from "../../../utilites/notifications.tsx";
import {formatCurrency} from "../../../utilites/currency.ts";
import {usePosSession} from "./usePosSession.ts";
import {PinGate} from "./PinGate.tsx";
import {CartLine, ChargeTab} from "./ChargeTab.tsx";
import {TopUpTab} from "./TopUpTab.tsx";
import {HistoryTab} from "./HistoryTab.tsx";
import classes from "./CashlessPos.module.scss";

const newClientReference = () =>
    (typeof crypto !== 'undefined' && 'randomUUID' in crypto)
        ? crypto.randomUUID()
        : `${Date.now()}-${Math.random().toString(36).slice(2)}`;

const CashlessPos = () => {
    const {salesPointShortId} = useParams();
    const {token, storeToken, clearToken} = usePosSession(String(salesPointShortId));
    const {data: salesPoint, isError} = useGetCashlessSalesPointPublic(salesPointShortId, token);

    const [wallet, setWallet] = useState<CashlessWalletPublic | null>(null);
    const [scannedId, setScannedId] = useState('');
    const [cart, setCart] = useState<CartLine[]>([]);
    const [recentTransactions, setRecentTransactions] = useState<CashlessTransaction[]>([]);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [reversingShortId, setReversingShortId] = useState<string | null>(null);
    const [scannerResetToken, setScannerResetToken] = useState(0);

    const currency = salesPoint?.currency ?? 'USD';
    const sellsProducts = (salesPoint?.products?.length ?? 0) > 0;

    const lookUpWallet = async (attendeePublicId: string) => {
        setScannedId(attendeePublicId);

        try {
            const {data} = await publicCashlessClient.getWalletAtSalesPoint(
                String(salesPointShortId), attendeePublicId, token,
            );
            setWallet(data);
        } catch (error: any) {
            showError(error?.response?.data?.message || t`That ticket could not be found.`);
        } finally {
            setScannedId('');
            setScannerResetToken((value) => value + 1);
        }
    };

    const resetCustomer = () => {
        setWallet(null);
        setCart([]);
        setScannedId('');
        setScannerResetToken((value) => value + 1);
    };

    const addLine = (product: Product, priceId: number, title: string, unitPrice: number) => {
        setCart((lines) => {
            const existing = lines.find((line) => line.product_price_id === priceId);

            if (existing) {
                return lines.map((line) => line.product_price_id === priceId
                    ? {...line, quantity: line.quantity + 1}
                    : line);
            }

            return [...lines, {
                product_id: Number(product.id),
                product_price_id: priceId,
                title,
                unitPrice,
                quantity: 1,
            }];
        });
    };

    const changeQuantity = (priceId: number, delta: number) => {
        setCart((lines) => lines
            .map((line) => line.product_price_id === priceId
                ? {...line, quantity: line.quantity + delta}
                : line)
            .filter((line) => line.quantity > 0));
    };

    const charge = async () => {
        if (!wallet?.attendee_public_id) {
            return;
        }

        setIsSubmitting(true);

        try {
            const {data} = await publicCashlessClient.createPurchase(String(salesPointShortId), {
                attendee_public_id: wallet.attendee_public_id,
                client_reference_id: newClientReference(),
                items: cart.map((line) => ({
                    product_id: line.product_id,
                    product_price_id: line.product_price_id,
                    quantity: line.quantity,
                })),
            }, token);

            showSuccess(t`Charged ${formatCurrency(Math.abs(data.amount), currency)} — ${formatCurrency(data.balance_after, currency)} left`);
            setRecentTransactions((transactions) => [
                {...data, attendee_public_id: wallet.attendee_public_id},
                ...transactions,
            ]);
            resetCustomer();
        } catch (error: any) {
            showError(error?.response?.data?.message || t`This payment could not be taken.`);
        } finally {
            setIsSubmitting(false);
        }
    };

    const topUp = async (amount: number, paymentMethod: CashlessStaffPaymentMethod) => {
        if (!wallet?.attendee_public_id) {
            return;
        }

        setIsSubmitting(true);

        try {
            const {data} = await publicCashlessClient.createStaffTopup(String(salesPointShortId), {
                attendee_public_id: wallet.attendee_public_id,
                client_reference_id: newClientReference(),
                amount,
                payment_method: paymentMethod,
            }, token);

            showSuccess(t`Added ${formatCurrency(amount, currency)} — balance is now ${formatCurrency(data.balance_after, currency)}`);
            setRecentTransactions((transactions) => [
                {...data, attendee_public_id: wallet.attendee_public_id},
                ...transactions,
            ]);
            resetCustomer();
        } catch (error: any) {
            showError(error?.response?.data?.message || t`This top-up could not be recorded.`);
        } finally {
            setIsSubmitting(false);
        }
    };

    const reverse = async (transactionShortId: string) => {
        setReversingShortId(transactionShortId);

        try {
            await publicCashlessClient.reverseTransaction(String(salesPointShortId), transactionShortId, token);
            showSuccess(t`Transaction undone`);
            setRecentTransactions((transactions) =>
                transactions.filter((transaction) => transaction.short_id !== transactionShortId));
        } catch (error: any) {
            showError(error?.response?.data?.message || t`This transaction could not be undone.`);
        } finally {
            setReversingShortId(null);
        }
    };

    if (isError) {
        return (
            <HomepageInfoMessage
                status="not_found"
                message={t`Sales point not found`}
                subtitle={t`This link is no longer valid. Ask the organizer for a new one.`}
            />
        );
    }

    if (!salesPoint) {
        return null;
    }

    if (salesPoint.requires_pin && !salesPoint.products) {
        return (
            <PinGate
                salesPointShortId={String(salesPointShortId)}
                salesPointName={salesPoint.name}
                onAuthenticated={storeToken}
            />
        );
    }

    return (
        <div className={classes.pos}>
            <header className={classes.header}>
                <div>
                    <h1 className={classes.salesPointName}>{salesPoint.name}</h1>
                    <span className={classes.eventName}>{salesPoint.event_title}</span>
                </div>
                {salesPoint.requires_pin && (
                    <button type="button" className={classes.lockButton} onClick={clearToken}>
                        {t`Lock till`}
                    </button>
                )}
            </header>

            <Tabs defaultValue={sellsProducts ? 'charge' : 'topup'} className={classes.tabs}>
                <Tabs.List grow>
                    {sellsProducts && (
                        <Tabs.Tab value="charge" leftSection={<IconReceipt size={16}/>}>
                            {t`Charge`}
                        </Tabs.Tab>
                    )}
                    <Tabs.Tab value="topup" leftSection={<IconCoin size={16}/>}>
                        {t`Top up`}
                    </Tabs.Tab>
                    <Tabs.Tab value="history" leftSection={<IconArrowsExchange size={16}/>}>
                        {t`History`}
                    </Tabs.Tab>
                </Tabs.List>

                {sellsProducts && <Tabs.Panel value="charge" className={classes.panel}>
                    <ChargeTab
                        salesPoint={salesPoint}
                        wallet={wallet}
                        scannedId={scannedId}
                        cart={cart}
                        isCharging={isSubmitting}
                        onScan={lookUpWallet}
                        onManualLookup={lookUpWallet}
                        onAddLine={addLine}
                        onChangeQuantity={changeQuantity}
                        onClear={resetCustomer}
                        onCharge={charge}
                        scannerResetToken={scannerResetToken}
                    />
                </Tabs.Panel>}

                <Tabs.Panel value="topup" className={classes.panel}>
                    <TopUpTab
                        salesPoint={salesPoint}
                        wallet={wallet}
                        isSubmitting={isSubmitting}
                        onScan={lookUpWallet}
                        onManualLookup={lookUpWallet}
                        onClear={resetCustomer}
                        onTopUp={topUp}
                        scannerResetToken={scannerResetToken}
                    />
                </Tabs.Panel>

                <Tabs.Panel value="history" className={classes.panel}>
                    <HistoryTab
                        transactions={recentTransactions}
                        currency={currency}
                        reversingShortId={reversingShortId}
                        onReverse={reverse}
                    />
                </Tabs.Panel>
            </Tabs>
        </div>
    );
};

export default CashlessPos;
