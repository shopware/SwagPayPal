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

        onboardingCallbackLive?: (authCode: string, sharedId: string) => void;
        onboardingCallbackSandbox?: (authCode: string, sharedId: string) => void;
    }
}

export {};
