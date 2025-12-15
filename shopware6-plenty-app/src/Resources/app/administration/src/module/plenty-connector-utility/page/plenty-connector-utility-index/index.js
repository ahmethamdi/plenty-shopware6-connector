import template from './plenty-connector-utility-index.html.twig';

const { Component } = Shopware;

Component.register('plenty-connector-utility-index', {
    template,

    data() {
        return {
            isClearing: false,
            isTesting: false,
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

        async testConnection() {
            this.isTesting = true;
            this.lastMessage = null;
            try {
                const res = await this.$http.post('/api/_action/plenty/test-connection');
                this.lastMessage = res?.data?.message || this.$tc('plenty-connector-utility.messages.connectionOk');
            } catch (e) {
                this.lastMessage = (e?.response?.data?.message) || this.$tc('plenty-connector-utility.messages.connectionFailed');
            } finally {
                this.isTesting = false;
            }
        },

        async syncProducts() {
            this.isSyncing = true;
            this.lastMessage = null;
            try {
                const res = await this.$http.post('/api/_action/plenty/sync-products-with-count');
                const processed = res?.data?.processed ?? null;
                this.lastMessage = processed !== null
                    ? this.$tc('plenty-connector-utility.messages.syncFinishedCount', processed)
                    : this.$tc('plenty-connector-utility.messages.syncTriggered');
            } catch (e) {
                this.lastMessage = (e?.response?.data?.errors?.[0]?.detail) || this.$tc('plenty-connector-utility.messages.syncFailed');
            } finally {
                this.isSyncing = false;
            }
        },
    },
});
