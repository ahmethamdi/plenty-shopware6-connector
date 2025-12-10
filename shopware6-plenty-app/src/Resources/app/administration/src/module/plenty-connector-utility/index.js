import './page/plenty-connector-utility-index';

const { Module } = Shopware;

Module.register('plenty-connector-utility', {
    type: 'plugin',
    name: 'plenty-connector-utility',
    title: 'plenty-connector-utility.general.mainMenuItemGeneral',
    description: 'Utility actions for Plentymarkets connector',
    color: '#0e71ba',
    icon: 'default-object-marketing',

    routes: {
        index: {
            component: 'plenty-connector-utility-index',
            path: 'index',
            meta: {
                parentPath: 'sw.settings.index',
            },
        },
    },

    settingsItem: [
        {
            group: 'plugins',
            to: 'plenty.connector.utility.index',
            icon: 'default-object-gear',
            label: 'plenty-connector-utility.general.mainMenuItemGeneral',
        },
    ],
});
