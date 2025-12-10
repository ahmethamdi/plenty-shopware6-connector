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
            } catch (e) {
                this.lastMessage = (e?.response?.data?.errors?.[0]?.detail) || this.$tc('plenty-connector-utility.messages.cacheFailed');
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
            } catch (e) {
                this.lastMessage = (e?.response?.data?.errors?.[0]?.detail) || this.$tc('plenty-connector-utility.messages.syncFailed');
            } finally {
                this.isSyncing = false;
            }
        },
    },
});
