import template from './plenty-connector-utility-index.html.twig';

const { Component } = Shopware;

Component.register('plenty-connector-utility-index', {
    template,

    data() {
        return {
            isClearing: false,
            isSyncing: false,
            lastMessage: null,
        };
    },

    methods: {
        async clearCache() {
            this.isClearing = true;
            this.lastMessage = null;
            try {
                await this.$http.post('/api/_action/plenty/cache-clear');
                this.lastMessage = this.$tc('plenty-connector-utility.messages.cacheCleared');
                this.createNotificationSuccess({
                    message: this.$tc('plenty-connector-utility.messages.cacheCleared')
                });
            } catch (e) {
                this.lastMessage = this.$tc('plenty-connector-utility.messages.cacheFailed');
                this.createNotificationError({
                    message: this.$tc('plenty-connector-utility.messages.cacheFailed')
                });
            } finally {
                this.isClearing = false;
            }
        },

        async syncProducts() {
            this.isSyncing = true;
            this.lastMessage = null;
            try {
                await this.$http.post('/api/_action/plenty/sync-products');
                this.lastMessage = this.$tc('plenty-connector-utility.messages.syncTriggered');
                this.createNotificationSuccess({
                    message: this.$tc('plenty-connector-utility.messages.syncTriggered')
                });
            } catch (e) {
                this.lastMessage = this.$tc('plenty-connector-utility.messages.syncFailed');
                this.createNotificationError({
                    message: this.$tc('plenty-connector-utility.messages.syncFailed')
                });
            } finally {
                this.isSyncing = false;
            }
        },
    },
});
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
import './module/plenty-connector-utility';
