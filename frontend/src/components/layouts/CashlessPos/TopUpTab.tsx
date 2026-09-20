import {t} from "@lingui/macro";
import {Button, NumberInput, Select, TextInput} from "@mantine/core";
import {useState} from "react";
import {CashlessSalesPointPublic, CashlessStaffPaymentMethod, CashlessWalletPublic} from "../../../types.ts";
import {InlineCameraScanner} from "../../common/InlineCameraScanner";
import {formatCurrency} from "../../../utilites/currency.ts";
import classes from "./CashlessPos.module.scss";

interface TopUpTabProps {
    salesPoint: CashlessSalesPointPublic;
    wallet: CashlessWalletPublic | null;
    isSubmitting: boolean;
    onScan: (attendeePublicId: string) => void;
    onManualLookup: (attendeePublicId: string) => void;
    onClear: () => void;
    onTopUp: (amount: number, paymentMethod: CashlessStaffPaymentMethod) => void;
    scannerResetToken: number;
}

export const TopUpTab = ({
                             salesPoint,
                             wallet,
                             isSubmitting,
                             onScan,
                             onManualLookup,
                             onClear,
                             onTopUp,
                             scannerResetToken,
                         }: TopUpTabProps) => {
    const [manualId, setManualId] = useState('');
    const [amount, setAmount] = useState<number | string>(20);
    const [paymentMethod, setPaymentMethod] = useState<CashlessStaffPaymentMethod>('CASH');
    const currency = salesPoint.currency ?? 'USD';

    if (!salesPoint.allow_staff_topups) {
        return (
            <div className={classes.emptyState}>
                <p>{t`This sales point is not allowed to top up balances.`}</p>
            </div>
        );
    }

    return (
        <div className={classes.topUpLayout}>
            {!wallet && (
                <>
                    <div className={classes.scannerFrame}>
                        <InlineCameraScanner onAttendeeScanned={onScan} clearHandledCodesToken={scannerResetToken}/>
                    </div>

                    <form
                        className={classes.manualLookup}
                        onSubmit={(event) => {
                            event.preventDefault();
                            if (manualId.trim()) {
                                onManualLookup(manualId.trim());
                                setManualId('');
                            }
                        }}
                    >
                        <TextInput
                            placeholder={t`Or type the ticket ID`}
                            value={manualId}
                            onChange={(event) => setManualId(event.currentTarget.value)}
                            data-testid="cashless-pos-topup-ticket-input"
                        />
                        <Button type="submit" variant="default">{t`Find`}</Button>
                    </form>
                </>
            )}

            {wallet && (
                <div className={classes.topUpForm}>
                    <div className={classes.customerCard}>
                        <span className={classes.customerName}>{wallet.attendee_name}</span>
                        <span className={classes.customerTicket}>{wallet.attendee_public_id}</span>
                        <span className={classes.customerBalance}>{formatCurrency(wallet.balance, currency)}</span>
                        <Button variant="subtle" size="compact-sm" onClick={onClear}>
                            {t`Serve someone else`}
                        </Button>
                    </div>

                    <NumberInput
                        label={t`Amount to add`}
                        size="lg"
                        min={0.01}
                        decimalScale={2}
                        value={amount}
                        onChange={setAmount}
                        data-testid="cashless-pos-topup-amount-input"
                    />

                    <Select
                        label={t`How did they pay?`}
                        size="lg"
                        value={paymentMethod}
                        onChange={(value) => setPaymentMethod((value ?? 'CASH') as CashlessStaffPaymentMethod)}
                        data={[
                            {value: 'CASH', label: t`Cash`},
                            {value: 'CARD_TERMINAL', label: t`Card terminal`},
                            {value: 'OTHER', label: t`Other`},
                        ]}
                    />

                    <Button
                        size="lg"
                        mt="md"
                        fullWidth
                        loading={isSubmitting}
                        disabled={Number(amount) <= 0}
                        onClick={() => onTopUp(Number(amount), paymentMethod)}
                        data-testid="cashless-pos-topup-submit-button"
                    >
                        {t`Add ${formatCurrency(Number(amount) || 0, currency)}`}
                    </Button>
                </div>
            )}
        </div>
    );
};
