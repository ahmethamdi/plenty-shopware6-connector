import './page/plenty-token-product-list';
import './page/plenty-token-product-detail';
import './page/plenty-token-order-list';

const { Module } = Shopware;

Module.register('plenty-token-market', {
    type: 'plugin',
    name: 'plenty-token-market',
    title: 'plenty-token-market.general.mainMenuItemGeneral',
    description: 'Manage Token Market products and orders',
    color: '#ffa500',
    icon: 'default-device-headset',

    routes: {
        'product-list': {
            component: 'plenty-token-product-list',
            path: 'products',
            meta: {
                parentPath: 'sw.settings.index',
            },
        },
        'product-detail': {
            component: 'plenty-token-product-detail',
            path: 'product/detail/:id',
            meta: {
                parentPath: 'plenty.token.market.product-list',
            },
        },
        'product-create': {
            component: 'plenty-token-product-detail',
            path: 'product/create',
            meta: {
                parentPath: 'plenty.token.market.product-list',
            },
        },
        'order-list': {
            component: 'plenty-token-order-list',
            path: 'orders',
            meta: {
                parentPath: 'sw.settings.index',
            },
        },
    },

    settingsItem: [
        {
            group: 'plugins',
            to: 'plenty.token.market.product-list',
            icon: 'default-device-headset',
            label: 'plenty-token-market.general.mainMenuItemProducts',
        },
        {
            group: 'plugins',
            to: 'plenty.token.market.order-list',
            icon: 'default-shopping-basket',
            label: 'plenty-token-market.general.mainMenuItemOrders',
        },
    ],
});
