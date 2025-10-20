import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import Default from '@/Layouts/Default.vue'
import 'vue-color/style.css';
import '../scss/styles.scss'

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('../views/Pages/**/*.vue', { eager: true });
        let page = pages[`../views/Pages/${name}.vue`];
        if (page.default.layout === undefined) {
            page.default.layout = Default;
        }
        return page;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
});