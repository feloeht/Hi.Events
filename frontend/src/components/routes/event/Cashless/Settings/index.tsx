import {t} from "@lingui/macro";
import {Button, NumberInput, Switch, TextInput} from "@mantine/core";
import {useForm} from "@mantine/form";
import {useEffect} from "react";
import {useParams} from "react-router";
import {PageBody} from "../../../../common/PageBody";
import {PageTitle} from "../../../../common/PageTitle";
import {Card} from "../../../../common/Card";
import {InputGroup} from "../../../../common/InputGroup";
import {useGetCashlessSettings} from "../../../../../queries/useGetCashlessSettings.ts";
import {useUpdateCashlessSettings} from "../../../../../mutations/useUpdateCashlessSettings.ts";
import {useGetEvent} from "../../../../../queries/useGetEvent.ts";
import {useFormErrorResponseHandler} from "../../../../../hooks/useFormErrorResponseHandler.tsx";
import {showSuccess} from "../../../../../utilites/notifications.tsx";
import {getCurrencySymbol} from "../../../../../utilites/currency.ts";

interface CashlessSettingsFormValues {
    cashless_enabled: boolean;
    cashless_min_topup_amount: number | string;
    cashless_allow_remaining_balance_refund: boolean;
    cashless_refund_deadline_at: string;
    cashless_online_topup_enabled: boolean;
    cashless_topup_fixed_fee: number | string;
    cashless_topup_percentage_fee: number | string;
}

const CashlessSettings = () => {
    const {eventId} = useParams();
    const {data: event} = useGetEvent(eventId);
    const {data: settings} = useGetCashlessSettings(eventId);
    const updateMutation = useUpdateCashlessSettings();
    const errorHandler = useFormErrorResponseHandler();

    const form = useForm<CashlessSettingsFormValues>({
        initialValues: {
            cashless_enabled: false,
            cashless_min_topup_amount: 5,
            cashless_allow_remaining_balance_refund: false,
            cashless_refund_deadline_at: '',
            cashless_online_topup_enabled: true,
            cashless_topup_fixed_fee: 0,
            cashless_topup_percentage_fee: 0,
        },
    });

    useEffect(() => {
        if (!settings) {
            return;
        }

        form.setValues({
            cashless_enabled: settings.cashless_enabled,
            cashless_min_topup_amount: settings.cashless_min_topup_amount,
            cashless_allow_remaining_balance_refund: settings.cashless_allow_remaining_balance_refund,
            cashless_refund_deadline_at: settings.cashless_refund_deadline_at?.slice(0, 16) ?? '',
            cashless_online_topup_enabled: settings.cashless_online_topup_enabled,
            cashless_topup_fixed_fee: settings.cashless_topup_fixed_fee,
            cashless_topup_percentage_fee: settings.cashless_topup_percentage_fee,
        });
    }, [settings]);

    const handleSubmit = form.onSubmit((values) => {
        updateMutation.mutate({
            eventId,
            settings: {
                cashless_enabled: values.cashless_enabled,
                cashless_min_topup_amount: Number(values.cashless_min_topup_amount),
                cashless_allow_remaining_balance_refund: values.cashless_allow_remaining_balance_refund,
                cashless_refund_deadline_at: values.cashless_refund_deadline_at || null,
                cashless_online_topup_enabled: values.cashless_online_topup_enabled,
                cashless_topup_fixed_fee: Number(values.cashless_topup_fixed_fee),
                cashless_topup_percentage_fee: Number(values.cashless_topup_percentage_fee),
            },
        }, {
            onSuccess: () => showSuccess(t`Cashless settings saved`),
            onError: (error) => errorHandler(form, error),
        });
    });

    return (
        <PageBody>
            <PageTitle
                subheading={t`Let attendees load money onto their ticket and pay with its QR code at your bars and stands.`}
            >
                {t`Cashless Settings`}
            </PageTitle>

            <Card>
                <form onSubmit={handleSubmit}>
                    <Switch
                        label={t`Enable cashless payments`}
                        description={t`Adds a top-up option to every ticket and lets your sales points take payment.`}
                        {...form.getInputProps('cashless_enabled', {type: 'checkbox'})}
                        data-testid="cashless-enabled-switch"
                    />

                    <NumberInput
                        mt="md"
                        label={t`Minimum top-up`}
                        description={t`Card fees make very small top-ups uneconomical.`}
                        prefix={getCurrencySymbol(event?.currency ?? 'USD')}
                        min={0.01}
                        decimalScale={2}
                        {...form.getInputProps('cashless_min_topup_amount')}
                    />

                    <Switch
                        mt="md"
                        label={t`Allow top-ups from the ticket page`}
                        description={t`Turn this off to take top-ups only at your sales points.`}
                        {...form.getInputProps('cashless_online_topup_enabled', {type: 'checkbox'})}
                        data-testid="cashless-online-topup-switch"
                    />

                    {form.values.cashless_online_topup_enabled && (
                        <InputGroup>
                            <NumberInput
                                mt="md"
                                label={t`Online top-up fee`}
                                description={t`Charged on top of the amount loaded, to cover card fees.`}
                                prefix={getCurrencySymbol(event?.currency ?? 'USD')}
                                min={0}
                                decimalScale={2}
                                {...form.getInputProps('cashless_topup_fixed_fee')}
                            />
                            <NumberInput
                                mt="md"
                                label={t`Online top-up fee (percentage)`}
                                description={t`Added to the fixed fee above.`}
                                suffix="%"
                                min={0}
                                max={100}
                                decimalScale={2}
                                {...form.getInputProps('cashless_topup_percentage_fee')}
                            />
                        </InputGroup>
                    )}

                    <Switch
                        mt="md"
                        label={t`Allow refunds of unspent balance`}
                        description={t`Lets you return what attendees did not spend, to their card or in person.`}
                        {...form.getInputProps('cashless_allow_remaining_balance_refund', {type: 'checkbox'})}
                    />

                    {form.values.cashless_allow_remaining_balance_refund && (
                        <TextInput
                            mt="md"
                            type="datetime-local"
                            label={t`Refunds close at`}
                            description={t`Leave blank to keep refunds open indefinitely.`}
                            {...form.getInputProps('cashless_refund_deadline_at')}
                        />
                    )}

                    <Button
                        type="submit"
                        mt="lg"
                        loading={updateMutation.isPending}
                        data-testid="cashless-settings-submit-button"
                    >
                        {t`Save settings`}
                    </Button>
                </form>
            </Card>
        </PageBody>
    );
};

export default CashlessSettings;
