declare module '*.svg' {
    const content: string
    export default content;
}

declare module '*.svg?url' {
    const content: string
    export default content;
}

declare module '*.svg?component' {
    import type { DefineComponent } from 'vue';
    // eslint-disable-next-line @typescript-eslint/no-empty-object-type, @typescript-eslint/no-explicit-any
    const component: DefineComponent<{}, {}, any>;
    export default component;
}

declare module '*.png' {
    const content: string
    export default content;
}

declare module '*.png?url' {
    const content: string
    export default content;
}
