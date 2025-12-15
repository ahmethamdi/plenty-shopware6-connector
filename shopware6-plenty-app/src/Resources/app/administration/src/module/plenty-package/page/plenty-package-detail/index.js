import template from './plenty-package-detail.html.twig';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;
const { mapPropertyErrors } = Shopware.Component.getComponentHelper();

Component.register('plenty-package-detail', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('notification'),
    ],

    data() {
        return {
            package: null,
            isLoading: false,
            isSaveSuccessful: false,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(),
        };
    },

    computed: {
        packageRepository() {
            return this.repositoryFactory.create('plenty_package');
        },

        categoryRepository() {
            return this.repositoryFactory.create('category');
        },

        customerRepository() {
            return this.repositoryFactory.create('customer');
        },

        ...mapPropertyErrors('package', [
            'name',
            'targetAmount',
            'tokenReward',
        ]),

        isCreateMode() {
            return this.$route.name === 'plenty.package.create';
        },
    },

    created() {
        this.loadEntityData();
    },

    methods: {
        loadEntityData() {
            this.isLoading = true;

            if (this.isCreateMode) {
                this.package = this.packageRepository.create(Shopware.Context.api);
                this.package.active = true;
                this.package.visibilityType = 'all';
                this.package.categoryIds = [];
                this.package.allowedCustomerIds = [];
                this.package.excludedCustomerIds = [];
                this.isLoading = false;
                return;
            }

            const criteria = new Criteria();

            this.packageRepository.get(this.$route.params.id, Shopware.Context.api, criteria)
                .then((entity) => {
                    this.package = entity;
                    this.isLoading = false;
                });
        },

        onSave() {
            this.isLoading = true;

            console.log('Saving package:', JSON.stringify(this.package, null, 2));

            return this.packageRepository.save(this.package, Shopware.Context.api)
                .then(() => {
                    this.isLoading = false;
                    this.isSaveSuccessful = true;
                    if (this.package.id) {
                        this.$router.push({ name: 'plenty.package.detail', params: { id: this.package.id } });
                    } else {
                        this.$router.push({ name: 'plenty.package.list' });
                    }
                })
                .catch((error) => {
                    console.error('Save error:', error);
                    console.error('Error response:', error.response);
                    if (error.response && error.response.data) {
                        console.error('Error details:', error.response.data);
                    }
                    this.isLoading = false;
                    this.createNotificationError({
                        message: this.$tc('plenty-package.detail.errorMessage'),
                    });
                });
        },

        onCancel() {
            this.$router.push({ name: 'plenty.package.list' });
        },
    },
});
