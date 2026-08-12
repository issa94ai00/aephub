import { createRouter, createWebHistory } from 'vue-router';
import WarehousesIndex from '../Pages/Admin/Warehouses/Index.vue';
import WarehousesForm from '../Pages/Admin/Warehouses/Form.vue';
import LinkProducts from '../Pages/Admin/Warehouses/LinkProducts.vue';
import ProductsIndex from '../Pages/Admin/Products/Index.vue';
import ProductsForm from '../Pages/Admin/Products/Form.vue';
import LinkWarehouses from '../Pages/Admin/Products/LinkWarehouses.vue';
import StockIndex from '../Pages/Admin/Stock/Index.vue';
import StockOrganize from '../Pages/Admin/Stock/Organize.vue';
import StockBalances from '../Pages/Admin/Stock/Balances.vue';
import Movements from '../Pages/Admin/Stock/Movements.vue';

const routes = [
    { path: '/admin/warehouses', name: 'admin.warehouses.index', component: WarehousesIndex },
    { path: '/admin/warehouses/create', name: 'admin.warehouses.create', component: WarehousesForm },
    { path: '/admin/warehouses/:warehouse/edit', name: 'admin.warehouses.edit', component: WarehousesForm },
    { path: '/admin/warehouses/:warehouse/link-products', name: 'admin.warehouses.link-products', component: LinkProducts },
    { path: '/admin/products', name: 'admin.products.index', component: ProductsIndex },
    { path: '/admin/products/create', name: 'admin.products.create', component: ProductsForm },
    { path: '/admin/products/:product/edit', name: 'admin.products.edit', component: ProductsForm },
    { path: '/admin/products/:product/link-warehouses', name: 'admin.products.link-warehouses', component: LinkWarehouses },
    { path: '/admin/stock', name: 'admin.stock.index', component: StockIndex },
    { path: '/admin/stock/balances', name: 'admin.stock.balances', component: StockBalances },
    { path: '/admin/stock/organize', name: 'admin.stock.organize', component: StockOrganize },
    { path: '/admin/stock/movements', name: 'admin.stock.movements', component: Movements },
    {
        path: '/admin/:pathMatch(.*)*',
        name: 'admin.full-page',
        beforeEnter: (to) => {
            window.location.assign(to.fullPath);
            return false;
        },
    },
];

export const router = createRouter({
    history: createWebHistory(),
    routes,
});

function pathToRegex(path) {
    return new RegExp('^' + path.replace(/:[^/]+/g, '[^/]+') + '$');
}

const spaMatchers = routes
    .filter((r) => r.name !== 'admin.full-page')
    .map((r) => pathToRegex(r.path));

export function isSpaPath(href) {
    if (!href) {
        return false;
    }
    let clean = String(href).split('?')[0];
    try {
        clean = new URL(clean, window.location.origin).pathname;
    } catch {
        // keep raw path
    }
    return spaMatchers.some((re) => re.test(clean));
}

export function toPath(url) {
    try {
        const u = new URL(url, window.location.origin);
        return u.pathname + u.search;
    } catch {
        return String(url);
    }
}
