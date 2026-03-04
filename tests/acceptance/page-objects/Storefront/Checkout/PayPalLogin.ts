import { PageObject } from '@shopware-ag/acceptance-test-suite';
import type { Page, Locator } from 'playwright-core';


export class PayPalLogin implements PageObject {
    
    public readonly eMailInput: Locator;
    public readonly passwordInput: Locator;
    
    public readonly nextButton: Locator;
    public readonly loginButton: Locator;


    public readonly page: Page;

    constructor(page: Page) {
        this.page = page;
        this.eMailInput = page.locator('[id^="email"]');
        this.passwordInput = page.locator('[id^="password"]');
        this.nextButton = page.getByRole('button', { name: 'Next' });
        this.loginButton = page.getByRole('button', { name: 'Log In' });
    }

    url(): string {
        throw new Error('PayPalLogin page can\'t be called directly.');
    }
}
