import iconComponents from '../app/assets/icons/icons';

const { Component } = Shopware;

export default (() => {
    return (iconComponents as VueComponent[]).map((component: VueComponent) => {
        return Component.register(component.name as string, component);
    });
})();
