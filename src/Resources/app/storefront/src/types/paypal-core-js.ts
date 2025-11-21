import type * as SDK from '@paypal/paypal-js/sdk-v6';

declare global {
    namespace PayPalCoreJS {
        export interface Namespace {
            createInstance: <T extends Components>(
                options: InstanceOptions<T>,
            ) => Promise<Instance<T>>;
        }

        export type Components = Exclude<SDK.Components, 'paypal-legacy-billing-agreements'> | 'googlepay-payments' | 'applepay-payments' | 'card-fields' | 'paypal-messages';
        export type AllComponents = [Components, ...Components[]];
        export type FundingSource = SDK.FundingSource | 'googlepay' | 'applepay' | 'advanced_cards';

        export type Instance<C extends Components = Components> = Omit<SDK.SdkInstance<[Extract<C, SDK.Components>]>, 'findEligibleMethods'>
            & (C extends 'googlepay-payments' ? PayPalCoreJS.GooglePay.Instance : never)
            & (C extends 'applepay-payments' ? PayPalCoreJS.ApplePay.Instance : never)
            & (C extends 'card-fields' ? PayPalCoreJS.CardFields.Instance : never)
            & (C extends 'paypal-messages' ? PayPalCoreJS.Messages.Instance : never)
            & PayPalCoreJS.FindEligibleMethods.Instance
        ;

        export type InstanceOptions<C extends Components = Components> = Omit<SDK.CreateInstanceOptions<[Extract<C, SDK.Components>]>, 'components'> & {
            components?: C[];
        };

        export type PaymentSessionOptions<T extends FundingSource = FundingSource> =
            | T extends 'paypal' ? SDK.PayPalOneTimePaymentSessionOptions : never
            | T extends 'paylater' ? SDK.PayLaterOneTimePaymentSessionOptions : never
            | T extends 'credit' ? SDK.PayPalOneTimePaymentSessionOptions : never
            | T extends 'venmo' ? SDK.VenmoOneTimePaymentSessionOptions : never
            | T extends 'googlepay' ? PayPalCoreJS.GooglePay.PaymentSessionOptions : never
            | T extends 'applepay' ? PayPalCoreJS.ApplePay.PaymentSessionOptions : never
            | T extends 'advanced_cards' ? PayPalCoreJS.CardFields.PaymentSessionOptions : never
        ;

        export type PaymentSession<T extends FundingSource = FundingSource> =
            | T extends 'paypal' ? SDK.OneTimePaymentSession : never
            | T extends 'paylater' ? SDK.OneTimePaymentSession : never
            | T extends 'credit' ? SDK.OneTimePaymentSession : never
            | T extends 'venmo' ? SDK.VenmoOneTimePaymentSession : never
            | T extends 'googlepay' ? PayPalCoreJS.GooglePay.PaymentSession : never
            | T extends 'applepay' ? PayPalCoreJS.ApplePay.PaymentSession : never
            | T extends 'advanced_cards' ? PayPalCoreJS.CardFields.PaymentSession : never
        ;

        export type PageTypes = SDK.PageTypes;

        export type LoadCoreScriptOptions = SDK.LoadCoreSdkScriptOptions;

        export interface HTMLPaypalButton extends HTMLElement {
            disabled: boolean;
            hidden: boolean;
            ariaDisabled: string;
            type: 'checkout' | 'buynow' | 'donate' | 'subscribe'; // more types are unknown
        }

        export interface HTMLVenmoButton extends HTMLPaypalButton {}

        export interface HTMLPaypalPayLaterButton extends HTMLElement {
            hidden: boolean;
            disabled: boolean;
            ariaDisabled: string;
            countryCode: string;
            productCode: string;
        }

        export interface HTMLVenmoButton extends HTMLPaypalButton {
        }

        export interface HTMLPaypalMessage extends HTMLElement, Messages.ContentOptions {
            autoBootstrap?: boolean;
            buyerCountry?: string;
            offerTypes?: string;
            getFetchContentOptions: () => Messages.FetchContentOptions;
            addEventListener(type: 'paypal-message-click', listener: (event: CustomEvent<{ config: unknown }>) => void): void;
            addEventListener(type: string, listener: EventListenerOrEventListenerObject, options?: boolean | AddEventListenerOptions): void;
        }

        export namespace FindEligibleMethods {
            export interface Instance {
                findEligibleMethods: (
                    findEligibleMethodsOptions: FindEligibleMethods.Options,
                ) => Promise<FindEligibleMethods.EligiblePaymentMethods>;
            }

            export interface Options extends SDK.FindEligibleMethodsOptions {}

            export type Details<T extends FundingSource> =
                | SDK.FindEligibleMethodsGetDetails<Extract<T, SDK.FundingSource>>
                | (T extends 'googlepay' ? GooglePay.PaymentMethodDetails : never)
                | (T extends 'applepay' ? ApplePay.PaymentMethodDetails : never)
                | (T extends 'advanced_cards' ? CardFields.PaymentMethodDetails : never)
            ;

            export interface EligiblePaymentMethods {
                isEligible: (paymentMethod: FundingSource) => boolean;
                getDetails: <T extends FundingSource>(
                    fundingSource: T,
                ) => FindEligibleMethods.Details<T>;
            }
        }

        export namespace CardFields {
            export interface Instance {
                createCardFieldsOneTimePaymentSession(options?: CardFields.PaymentSessionOptions): CardFields.PaymentSession;
            }

            export interface PaymentSessionOptions {

            }

            export interface PaymentSession {
                createCardFieldsComponent(config: CardFields.CreateCardFieldsComponentOptions): HTMLElement;
                submit(orderId: string, options: CardFields.SubmitOptions): Promise<void>;
            }

            export interface CreateCardFieldsComponentOptions {
                type: string;
                placeholder?: string;
                style?: Record<string, unknown>;
            }

            export interface SubmitOptions {
                billingAddress?: any;
                shippingAddress?: any;
            }

            export interface PaymentMethodDetails {
                cobrandedEnabled: boolean;
                supportsInstallments: boolean;
                vendors: CardFields.PaymentMethodDetailsVendor[];
            }

            export interface PaymentMethodDetailsVendor {
                branded: boolean;
                can_be_vaulted: boolean;
                eligible: boolean;
                network: string;
            }
        }

        export namespace GooglePay {
            export interface Instance {
                createGooglePayOneTimePaymentSession(options?: GooglePay.PaymentSessionOptions): GooglePay.PaymentSession;
            }

            export interface PaymentSessionOptions {

            }

            export interface PaymentSession {
                getGooglePayConfig: () => Promise<GooglePay.Config>;
                confirmOrder(data: GooglePay.ConfirmOrderOptions): Promise<GooglePay.PaymentSessionOutput>;
                initiatePayerAction(data: { orderId: string }): Promise<void>;
            }

            export interface Config extends Pick<google.payments.api.PaymentDataRequest, 'apiVersion' | 'apiVersionMinor' | 'allowedPaymentMethods' | 'merchantInfo'> {
                isEligible: boolean;
                countryCode: string;
            }

            export interface ConfirmOrderOptions {
                orderId: string;
                paymentMethodData: google.payments.api.PaymentMethodData;
            }

            export interface PaymentSessionOutput {
                status: string;
            }


            export interface PaymentMethodDetails extends SDK.BaseEligiblePaymentMethodDetails {
                config: Omit<GooglePay.Config, 'isEligible' | 'countryCode'> & {
                    eligible: boolean;
                    merchantCountry: string;
                };
            }
        }

        export namespace ApplePay {
            export interface Instance {
                createApplePayOneTimePaymentSession(options?: ApplePay.PaymentSessionOptions): ApplePay.PaymentSession;
            }

            export interface PaymentSessionOptions {

            }

            export interface PaymentSession {
                config: () => Promise<ApplePay.Config>;
                confirmOrder(data: ApplePay.ConfirmOrderOptions): Promise<void>;
                validateMerchant(data: ApplePay.ValidateMerchantOptions): Promise<ApplePay.ValidateMerchantOutput>;
            }

            export interface Config {
                isEligible: boolean;
                merchantCountry: string;
                supportedNetworks: string[];
                merchantCapabilities: ApplePayJS.ApplePayMerchantCapability[];
                tokenNotificationUrl: string;
                currencyCode: string;
                countryCode: string;
            }

            export interface ConfirmOrderOptions {
                orderId: string;
                token: ApplePayJS.ApplePayPaymentToken;
                billingContact: unknown;
            }

            export interface ValidateMerchantOptions {
                validationUrl: string;
                displayName?: string;
                [key: string]: unknown;
            }

            export interface ValidateMerchantOutput {
                merchantSession: unknown;
            }

            export interface PaymentMethodDetails extends SDK.BaseEligiblePaymentMethodDetails {
                config: Omit<Config, 'currencyCode' | 'countryCode'> & {
                    eligible: boolean;
                };
            }
        }

        export namespace Messages {
            export interface Instance {
                createPayPalMessages(options?: Messages.CreatePayPalMessagesOptions): Promise<Messages.PayPalMessages>;
            }

            export interface CreatePayPalMessagesOptions {
            }

            export interface PayPalMessages {
                fetchContent(options: FetchContentOptions): Promise<Content>;
                createLearnMore(options: LearnMoreOptions): Promise<LearnMore>;
            }

            export interface ContentOptions {
                amount?: string;
                currencyCode?: string;
                logoType?: 'MONOGRAM' | 'TEXT' | 'WORDMARK';
                textColor?: 'BLACK' | 'WHITE' | 'MONOCHROME';
                logoPosition?: 'INLINE' | 'RIGHT' | 'TOP' | 'LEFT';
            }

            export interface FetchContentOptions extends ContentOptions {
                onContentReady?: () => void;
                onReady?: () => void;
                onTemplateReady?: () => void;
            }

            export interface LearnMoreOptions {
                amount?: string;
                presentationMode?: 'AUTO' | 'MODAL' | 'POPUP' | 'REDIRECT';
                onApply?: () => void;
                onCalculate?: () => void;
                onShow?: () => void;
                onClose?: () => void;
            }

            export interface Content {
                update(data: ContentOptions): Promise<void>;
            }

            export interface LearnMore {
                open(data: unknown): Promise<void>;
            }
        }
    }
}
