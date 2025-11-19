import ElementLoadingIndicatorUtil from 'src/utility/loading-indicator/element-loading-indicator.util';

export class PaypalButtonHelper {
    public static hide(el: HTMLElement): void {
        ElementLoadingIndicatorUtil.remove(el);
        el.setAttribute('disabled', 'true');
        el.setAttribute('hidden', '');
        el.classList.add('d-none');
    }

    public static load(el: HTMLElement): void {
        ElementLoadingIndicatorUtil.create(el);
        el.setAttribute('disabled', 'true');
        el.removeAttribute('hidden');
        el.classList.remove('d-none');
    }

    public static enable(el: HTMLElement): void {
        ElementLoadingIndicatorUtil.remove(el);
        el.removeAttribute('disabled');
        el.removeAttribute('hidden');
        el.classList.remove('d-none');
    }

    public static disable(el: HTMLElement): void {
        ElementLoadingIndicatorUtil.remove(el);
        el.setAttribute('disabled', 'true');
        el.classList.add('is-disabled');
    }
}
