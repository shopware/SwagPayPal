import iconComponents from '../app/assets/icons/icons';

const { Component } = Shopware;

export default (() => {
    return iconComponents.map((component) => Component.register(component.name, component));
})();
