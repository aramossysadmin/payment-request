export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    roles?: string[];
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type AuthPermissions = {
    viewDashboard: boolean;
    viewPaymentRequests: boolean;
    createPaymentRequests: boolean;
    viewPaymentRequestDetail: boolean;
    updatePaymentRequests: boolean;
    viewStrategies: boolean;
};

export type Auth = {
    user: User;
    can: AuthPermissions;
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
