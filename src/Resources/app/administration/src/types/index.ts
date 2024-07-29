import type { AxiosError } from 'axios';
import type { components, operations } from 'src/types/openapi';

type AsKey<T extends string> = T extends `${infer N extends number}` ? N : T;

type Traverse<T, P extends string> = AsKey<P> extends keyof T
    ? T[AsKey<P>]
    : P extends `${infer U}.${infer R}`
        ? AsKey<U> extends keyof T
            ? Traverse<T[AsKey<U>], R>
            : never
        : never;

type PartialKey<T, K extends string> = T extends `${K}${infer U}` ? U : never;

export type V1<T extends PartialKey<keyof components['schemas'], 'swag_paypal_v1_'>> = components['schemas'][`swag_paypal_v1_${T}`];

export type V2<T extends PartialKey<keyof components['schemas'], 'swag_paypal_v2_'>> = components['schemas'][`swag_paypal_v2_${T}`];

export type V3<T extends PartialKey<keyof components['schemas'], 'swag_paypal_v3_'>> = components['schemas'][`swag_paypal_v3_${T}`];

export type Setting<T extends PartialKey<keyof components['schemas'], 'swag_paypal_setting_'>> = components['schemas'][`swag_paypal_setting_${T}`];

export namespace Api {
    export type Operations<T extends keyof operations> =
        // Does the operation have a 200 response?
        Traverse<operations, `${T}.responses.200`> extends never
            ? Traverse<operations, `${T}.responses.204`> extends never
                ? unknown // operation is missing content -> unknown
                : never // 204 (No content) has no content
            : Traverse<operations, `${T}.responses.200.content.application/json`>;
}

export type { SystemConfig } from './system-config';
export type ErrorState = { code: number; detail: string };

export type HttpError = ShopwareHttpError&{
    meta?: {
        // parameters of a PayPalApiException
        parameters?: {
            message?: string;
            name?: string;
            issue?: string;
            // part of PayPalPos
            salesChannelIds?: string[];
        };
    };
};

export type ServiceError = AxiosError<{ errors: HttpError[] }>;
