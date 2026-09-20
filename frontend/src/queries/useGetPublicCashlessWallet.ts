import {useQuery} from "@tanstack/react-query";
import {publicCashlessClient} from "../api/cashless-public.client.ts";
import {IdParam} from "../types.ts";

export const GET_PUBLIC_CASHLESS_WALLET_QUERY_KEY = 'getPublicCashlessWallet';

export const useGetPublicCashlessWallet = (eventId: IdParam, attendeeShortId: IdParam) => {
    return useQuery({
        queryKey: [GET_PUBLIC_CASHLESS_WALLET_QUERY_KEY, eventId, attendeeShortId],
        queryFn: async () => {
            const {data} = await publicCashlessClient.getWallet(eventId, attendeeShortId);
            return data;
        },
        enabled: !!eventId && !!attendeeShortId,
        retry: false,
    });
};
