import { PageObject } from '@shopware-ag/acceptance-test-suite';
import type { Page, Locator } from 'playwright-core';

export class PayPalLogin implements PageObject {
    public eMailInput!: Locator;
    public passwordInput!: Locator;

    public nextButton!: Locator;
    public loginButton!: Locator;

    public page!: Page;

    constructor(page: Page) {
        this.setPage(page);
    }

    setPage(page: Page): void {
        this.page = page;
        this.eMailInput = page.getByRole('textbox', { name: 'email' }).first();
        this.passwordInput = page.getByRole('textbox', { name: 'password' }).first();
        this.nextButton = page.getByRole('button', { name: 'Next' });
        this.loginButton = page.getByRole('button', { name: 'Log In' });
    }

    url(): string {
        throw new Error('PayPalLogin page can\'t be called directly.');
    }
}
