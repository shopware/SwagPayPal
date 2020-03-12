import iconComponents from '../app/assets/icons/icons';

const { Component } = Shopware;

export default (() => {
    return iconComponents.map((component) => {
        return Component.register(component.name, component);
    });
})();
