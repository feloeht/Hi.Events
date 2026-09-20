import {useMutation} from "@tanstack/react-query";
import {publicCashlessClient} from "../api/cashless-public.client.ts";
import {IdParam} from "../types.ts";

export const useCreatePublicCashlessTopup = () => {
    return useMutation({
        mutationFn: ({eventId, attendeeShortId, amount}: {
            eventId: IdParam,
            attendeeShortId: IdParam,
            amount: number
        }) => publicCashlessClient.createTopup(eventId, attendeeShortId, amount),
    });
};
