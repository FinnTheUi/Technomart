<?php

return [
    'enabled' => env('DATA_RETENTION_ENABLED', false),
    'audit_days' => (int) env('AUDIT_RETENTION_DAYS', 730),
    'message_days' => (int) env('MESSAGE_RETENTION_DAYS', 365),
    'read_notification_days' => (int) env('READ_NOTIFICATION_RETENTION_DAYS', 90),
    'payment_proof_days' => (int) env('PAYMENT_PROOF_RETENTION_DAYS', 365),
];
