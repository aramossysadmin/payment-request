export type PaymentPolicyPayload = {
    capture: {
        canAct: boolean;
        isWindowActive: boolean;
        opensAt: string;
        closesAt: string;
        opensAtLabel: string;
        closesAtLabel: string;
        windowLabel: string;
    };
    submit: {
        canAct: boolean;
        isWindowActive: boolean;
        opensAt: string | null;
        closesAt: string | null;
        opensAtLabel: string | null;
        closesAtLabel: string | null;
        windowLabel: string;
    };
    provision: {
        nextDate: string;
        label: string;
    };
    warningMinutesBeforeClose: number;
    isOverride: boolean;
};
