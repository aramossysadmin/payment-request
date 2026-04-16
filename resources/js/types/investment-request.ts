export type InvestmentRequestStatus = {
    name: string;
    label: string;
    color: 'warning' | 'info' | 'purple' | 'success' | 'gray' | 'danger' | 'orange';
};

export type InvestmentRequestApproval = {
    id: number;
    stage: 'department' | 'administration' | 'treasury';
    level: 1 | 2;
    status: 'pending' | 'approved' | 'rejected';
    comments: string | null;
    responded_at: string | null;
    user: {
        id: number;
        name: string;
    };
    created_at: string;
};

export type InvestmentRequest = {
    id: number;
    uuid: string;
    folio_number: number;
    provider: string;
    rfc: string | null;
    contact_name: string | null;
    contact_email: string | null;
    contact_phone: string | null;
    invoice_folio: string | null;
    description: string | null;
    payment_type: {
        id: number;
        name: string;
        slug: string;
        invoice_documents_mode: 'disabled' | 'optional' | 'required';
        additional_documents_mode: 'disabled' | 'optional' | 'required';
    };
    project: {
        id: number;
        name: string;
    } | null;
    advance_documents: string[] | null;
    status: InvestmentRequestStatus;
    iva_rate: { value: string; label: string };
    subtotal: string;
    iva: string;
    retention: boolean;
    total: string;
    number_purchase_invoices: number | null;
    number_vendor_payments: number | null;
    user: {
        id: number;
        name: string;
    };
    department: {
        id: number;
        name: string;
    };
    currency: {
        id: number;
        name: string;
        prefix: string;
    };
    branch: {
        id: number;
        name: string;
    };
    expense_concept: {
        id: number;
        name: string;
    };
    investment_expense_concept: {
        id: number;
        name: string;
    } | null;
    is_addendum: boolean;
    remaining_balance: string;
    group_budget: string | null;
    group_paid: string | null;
    group_remaining: string | null;
    approvals: InvestmentRequestApproval[];
    created_at: string;
    updated_at: string;
};
