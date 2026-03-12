import type { PaymentRequest } from './payment-request';

export type DashboardStats = {
    pendingCount: number;
    pendingByStage: {
        department: number;
        administration: number;
        treasury: number;
    };
    pendingApprovalCount: number;
    monthlyTotal: string;
};

export type ChartDataPoint = {
    month: string;
    count: number;
};

export type DashboardPageProps = {
    isAuthorizer: boolean;
    isSuperAdmin: boolean;
    stats?: DashboardStats;
    recentRequests?: PaymentRequest[];
    pendingApprovals?: PaymentRequest[];
    chartData?: ChartDataPoint[];
};
