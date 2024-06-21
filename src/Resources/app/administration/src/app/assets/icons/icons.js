export default (() => {
    const context = require.context('./svg', false, /svg$/);

    return context.keys().map((item) => ({
        name: item.split('.')[1].split('/')[1],
        functional: true,
        render(createElement, { data }) {
            return createElement('span', {
                class: [data.staticClass, data.class],
                style: data.style,
                attrs: data.attrs,
                on: data.on,
                domProps: {
                    innerHTML: context(item),
                },
            });
        },
    }));
})();
