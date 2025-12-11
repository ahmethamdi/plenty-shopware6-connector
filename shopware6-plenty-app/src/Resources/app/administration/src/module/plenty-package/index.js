import './page/plenty-package-list';
import './page/plenty-package-detail';

const { Module } = Shopware;

Module.register('plenty-package', {
    type: 'plugin',
    name: 'plenty-package',
    title: 'plenty-package.general.mainMenuItemGeneral',
    description: 'Manage customer reward packages',
    color: '#ff3d58',
    icon: 'default-shopping-paper-bag-product',

    routes: {
        list: {
            component: 'plenty-package-list',
            path: 'list',
            meta: {
                parentPath: 'sw.settings.index',
            },
        },
        detail: {
            component: 'plenty-package-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'plenty.package.list',
            },
        },
        create: {
            component: 'plenty-package-detail',
            path: 'create',
            meta: {
                parentPath: 'plenty.package.list',
            },
        },
    },

    settingsItem: [
        {
            group: 'plugins',
            to: 'plenty.package.list',
            icon: 'default-shopping-paper-bag-product',
            label: 'plenty-package.general.mainMenuItemGeneral',
        },
    ],
});
