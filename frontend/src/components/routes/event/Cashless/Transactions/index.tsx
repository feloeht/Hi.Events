import {t} from "@lingui/macro";
import {Button} from "@mantine/core";
import {IconDownload} from "@tabler/icons-react";
import {useState} from "react";
import {useParams} from "react-router";
import {PageBody} from "../../../../common/PageBody";
import {PageTitle} from "../../../../common/PageTitle";
import {ToolBar} from "../../../../common/ToolBar";
import {SortSelector} from "../../../../common/SortSelector";
import {TableSkeleton} from "../../../../common/TableSkeleton";
import {Pagination} from "../../../../common/Pagination";
import {CashlessTransactionTable} from "../../../../common/CashlessTransactionTable";
import {useFilterQueryParamSync} from "../../../../../hooks/useFilterQueryParamSync.ts";
import {useGetCashlessTransactions} from "../../../../../queries/useGetCashlessTransactions.ts";
import {useGetEvent} from "../../../../../queries/useGetEvent.ts";
import {cashlessClient} from "../../../../../api/cashless.client.ts";
import {downloadBinary} from "../../../../../utilites/download.ts";
import {withLoadingNotification} from "../../../../../utilites/withLoadingNotification.tsx";
import {IdParam, QueryFilters} from "../../../../../types.ts";

const CashlessTransactions = () => {
    const {eventId} = useParams();
    const [searchParams, setSearchParams] = useFilterQueryParamSync();
    const {data: event} = useGetEvent(eventId);
    const {data: transactionsData} = useGetCashlessTransactions(eventId, searchParams as QueryFilters);
    const [downloadPending, setDownloadPending] = useState(false);

    const transactions = transactionsData?.data;
    const pagination = transactionsData?.meta;

    const handleExport = async (eventId: IdParam) => {
        await withLoadingNotification(async () => {
            setDownloadPending(true);
            const blob = await cashlessClient.exportTransactions(eventId);
            downloadBinary(blob, 'cashless-transactions.xlsx');
        }, {
            loading: {
                title: t`Exporting transactions`,
                message: t`Please wait while we prepare your cashless transactions for export...`,
            },
            success: {
                title: t`Transactions exported`,
                message: t`Your cashless transactions have been exported successfully.`,
                onRun: () => setDownloadPending(false),
            },
            error: {
                title: t`Failed to export transactions`,
                message: t`Please try again.`,
                onRun: () => setDownloadPending(false),
            },
        });
    };

    return (
        <PageBody>
            <PageTitle
                subheading={t`Every top-up, purchase, reversal and refund, in the order it happened.`}
            >
                {t`Cashless Transactions`}
            </PageTitle>

            <ToolBar
                filterComponent={pagination?.allowed_sorts ? (
                    <SortSelector
                        selected={searchParams.sortBy && searchParams.sortDirection
                            ? searchParams.sortBy + ':' + searchParams.sortDirection
                            : pagination.default_sort + ':' + pagination.default_sort_direction}
                        options={pagination.allowed_sorts}
                        onSortSelect={(key, sortDirection) => setSearchParams({sortBy: key, sortDirection})}
                    />
                ) : undefined}
            >
                <Button
                    onClick={() => handleExport(eventId)}
                    rightSection={<IconDownload size={14}/>}
                    color="green"
                    loading={downloadPending}
                    size="sm"
                    data-testid="cashless-transactions-export-button"
                >
                    {t`Export`}
                </Button>
            </ToolBar>

            <TableSkeleton isVisible={!transactions}/>

            {transactions && (
                <CashlessTransactionTable
                    transactions={transactions}
                    currency={event?.currency ?? 'USD'}
                    timezone={event?.timezone ?? 'UTC'}
                />
            )}

            {(!!transactions?.length && (pagination?.last_page || 0) > 1) && (
                <Pagination
                    value={searchParams.pageNumber}
                    onChange={(value) => setSearchParams({pageNumber: value})}
                    total={Number(pagination?.last_page)}
                />
            )}
        </PageBody>
    );
};

export default CashlessTransactions;
