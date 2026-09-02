import type * as SDK from '@paypal/paypal-js/sdk-v6';
import 'applepayjs';

declare global {
    namespace PayPalCoreJS {
        export interface Namespace extends SDK.PayPalV6Namespace {
        }

        export type Components = SDK.Components;
        export type FundingSource = SDK.FundingSource;

        export type SdkInstance<T extends readonly Components[]> = SDK.SdkInstance<T>;

        export type PaymentSessionOptions<T extends FundingSource = FundingSource> =
            | T extends 'paypal' ? SDK.PayPalOneTimePaymentSessionOptions : never
            | T extends 'paylater' ? SDK.PayLaterOneTimePaymentSessionOptions : never
            | T extends 'credit' ? SDK.PayPalOneTimePaymentSessionOptions : never
            | T extends 'venmo' ? SDK.VenmoOneTimePaymentSessionOptions : never
            | T extends 'googlepay' ? { t?: never } : never
            | T extends 'applepay' ? { t?: never } : never
            | T extends 'advanced_cards' ? SDK.OneTimePaymentSubmitOptions : never
        ;

        export type PaymentSession<T extends FundingSource = FundingSource> =
            | T extends 'paypal' ? SDK.OneTimePaymentSession : never
            | T extends 'paylater' ? SDK.OneTimePaymentSession : never
            | T extends 'credit' ? SDK.OneTimePaymentSession : never
            | T extends 'venmo' ? SDK.VenmoOneTimePaymentSession : never
            | T extends 'googlepay' ? SDK.GooglePayOneTimePaymentSession : never
            | T extends 'applepay' ? SDK.ApplePayOneTimePaymentSession : never
            | T extends 'advanced_cards' ? SDK.CardFieldsOneTimePaymentSession : never
        ;

        export type PageTypes = SDK.PageTypes;

        export type HTMLPaypalButton = SDK.PayPalButtonElement;
        export type HTMLVenmoButton = SDK.PayPalButtonElement;
        export type HTMLPaypalPayLaterButton = SDK.PayPalPayLaterButtonElement;

        export type HTMLPaypalMessage = SDK.PayPalMessageElement & {
            logoPosition: SDK.LogoPosition;
            logoType: SDK.LogoType;
            getFetchContentOptions: () => SDK.FetchContentOptions;
            addEventListener(type: 'paypal-message-click', listener: (event: CustomEvent<{ config: SDK.LearnMoreOptions }>) => void): void;
            addEventListener(type: string, listener: EventListenerOrEventListenerObject, options?: boolean | AddEventListenerOptions): void;
        };

        export namespace FindEligibleMethods {
            export type Details<T extends FundingSource> = SDK.FindEligibleMethodsGetDetails<Extract<T, SDK.FundingSource>>;

            export type EligiblePaymentMethods = SDK.EligiblePaymentMethodsOutput;
        }

        export namespace Messages {
            export type ContentOptions = SDK.FetchContentOptions;
            export type PayPalMessages = SDK.PayPalMessagesSession;
        }

        export namespace GooglePay {
            export type Config = OmitReadonly<SDK.GooglePayConfig> & {
                allowedPaymentMethods: google.payments.api.PaymentDataRequest['allowedPaymentMethods'];
            };
            export type PaymentDataRequest = SDK.GooglePayPaymentDataRequest;
            export type PaymentMethodData = SDK.GooglePayPaymentMethodData;
        }

        export namespace ApplePay {
            export type Config = SDK.ApplePayConfig;
        }
    }
}
