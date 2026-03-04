export type PaymentRequestStatus = {
    name: string;
    label: string;
    color: 'warning' | 'info' | 'purple' | 'success' | 'gray' | 'danger' | 'orange';
};

export type PaymentTypeOption = {
    value: string;
    label: string;
};

export type Currency = {
    id: number;
    name: string;
};

export type Branch = {
    id: number;
    name: string;
};

export type ExpenseConcept = {
    id: number;
    name: string;
};

export type Department = {
    id: number;
    name: string;
};

export type PaymentRequestApproval = {
    id: number;
    stage: 'department' | 'administration' | 'treasury';
    status: 'pending' | 'approved' | 'rejected';
    comments: string | null;
    responded_at: string | null;
    user: {
        id: number;
        name: string;
    };
    created_at: string;
};

export type PaymentRequest = {
    id: number;
    folio_number: number;
    provider: string;
    invoice_folio: string;
    description: string | null;
    payment_type: PaymentTypeOption;
    advance_documents: string[] | null;
    status: PaymentRequestStatus;
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
    department: Department;
    currency: Currency;
    branch: Branch;
    expense_concept: ExpenseConcept;
    approvals: PaymentRequestApproval[];
    created_at: string;
    updated_at: string;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type PaginatedData<T> = {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
        links: PaginationLink[];
    };
};
