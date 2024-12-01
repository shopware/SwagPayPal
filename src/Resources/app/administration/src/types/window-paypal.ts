export declare type AppSignup = {
    Embedded: {
        render: () => void;
        timeout?: NodeJS.Timeout;
    };

    MiniBrowser: {
        init: (config: { url: string }) => void;
        timeout?: NodeJS.Timeout;
    };

    render: () => void;
    setup: () => void;
    timeout?: NodeJS.Timeout;
};

export declare type PAYPAL = {
    apps: {
        readonly domain: string;
        readonly ppobjects: string;
        readonly signupSrc: string;
        readonly experience: string;

        Signup: AppSignup;
    };
};

declare global {
    interface Window {
        PAYPAL?: PAYPAL;

        /**
         * @deprecated tag:v10.0.0 - Will be removed.
         */
        onboardingCallbackLive?: (authCode: string, sharedId: string) => void;

        /**
         * @deprecated tag:v10.0.0 - Will be removed.
         */
        onboardingCallbackSandbox?: (authCode: string, sharedId: string) => void;

        [key: `onboardingCallback${string}`]: undefined | ((authCode: string, sharedId: string) => void);
    }
}

export {};
