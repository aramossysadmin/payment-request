<?php

use App\Models\Branch;
use App\Models\Currency;
use App\Models\Department;
use App\Models\ExpenseConcept;
use App\Models\InvestmentExpenseCategory;
use App\Models\InvestmentExpenseConcept;
use App\Models\InvestmentPaymentApproval;
use App\Models\InvestmentPaymentBatch;
use App\Models\InvestmentPaymentRequest;
use App\Models\InvestmentRequest;
use App\Models\InvestmentRequestApproval;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestApproval;
use App\Models\PaymentType;
use App\Models\Position;
use App\Models\Project;
use App\Models\Role;
use App\Models\Society;
use App\Models\User;
use App\Models\WeeklyPaymentSchedule;
use App\Models\WeeklyPaymentScheduleApproval;
use App\Models\WeeklyPaymentScheduleItem;
use Z3d0X\FilamentLogger\Loggers\AccessLogger;
use Z3d0X\FilamentLogger\Loggers\ModelLogger;
use Z3d0X\FilamentLogger\Loggers\NotificationLogger;
use Z3d0X\FilamentLogger\Loggers\ResourceLogger;
use Z3d0X\FilamentLogger\Resources\ActivityResource;

return [
    'datetime_format' => 'd/m/Y H:i:s',
    'date_format' => 'd/m/Y',

    'activity_resource' => ActivityResource::class,
    'scoped_to_tenant' => false,
    'navigation_sort' => null,

    'resources' => [
        'enabled' => true,
        'log_name' => 'Resource',
        'logger' => ResourceLogger::class,
        'color' => 'success',

        'exclude' => [
            // Evita doble logging: ActivityResource es del package
            ActivityResource::class,
        ],
        'cluster' => null,
        'navigation_group' => 'Auditoría',
    ],

    'access' => [
        'enabled' => true,
        'logger' => AccessLogger::class,
        'color' => 'danger',
        'log_name' => 'Access',
    ],

    'notifications' => [
        'enabled' => true,
        'logger' => NotificationLogger::class,
        'color' => null,
        'log_name' => 'Notification',
    ],

    'models' => [
        'enabled' => true,
        'log_name' => 'Model',
        'color' => 'warning',
        'logger' => ModelLogger::class,
        'register' => [
            Branch::class,
            Currency::class,
            Department::class,
            ExpenseConcept::class,
            InvestmentExpenseCategory::class,
            InvestmentExpenseConcept::class,
            InvestmentPaymentApproval::class,
            InvestmentPaymentBatch::class,
            InvestmentPaymentRequest::class,
            InvestmentRequest::class,
            InvestmentRequestApproval::class,
            PaymentRequest::class,
            PaymentRequestApproval::class,
            PaymentType::class,
            Position::class,
            Project::class,
            Role::class,
            Society::class,
            User::class,
            WeeklyPaymentSchedule::class,
            WeeklyPaymentScheduleApproval::class,
            WeeklyPaymentScheduleItem::class,
        ],
    ],

    'custom' => [
        // [
        //     'log_name' => 'Custom',
        //     'color' => 'primary',
        // ]
    ],
];
