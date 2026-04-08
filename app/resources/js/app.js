import { createInertiaApp } from '@inertiajs/vue3'
import Default from '@/Layouts/Default.vue'
import '../scss/styles.scss'

createInertiaApp({
    pages: '../views/Pages',
    layout: () => Default,
});