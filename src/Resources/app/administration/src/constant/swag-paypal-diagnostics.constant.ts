import type * as PayPal from 'SwagPayPal/types';

export type Severity = PayPal.Diagnostics<'webhook_finding'>['severity'];

export type DiagnosticsStatus = 'error' | 'warning' | 'info' | 'passed' | 'not-observed';

export const SEVERITY_STATUSES: Record<Severity, DiagnosticsStatus> = {
    error: 'error',
    warning: 'warning',
    notice: 'info',
    unknown: 'not-observed',
};

// shows the time of the browser, not of the Admin user, like the log timestamps in the browser console
export const DATE_FORMAT: Intl.DateTimeFormatOptions & { skipTimezoneConversion: boolean } = {
    skipTimezoneConversion: true,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
};
