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

            // Always pass a Shopware Entity to the repository
            const entity = this.isCreateMode
                ? this.packageRepository.create(Shopware.Context.api)
                : this.package;

            entity.name = this.package.name;
            entity.targetAmount = this.package.targetAmount;
            entity.tokenReward = this.package.tokenReward;
            entity.active = this.package.active;
            entity.visibilityType = this.package.visibilityType;
            entity.categoryIds = this.package.categoryIds || [];
            entity.allowedCustomerIds = this.package.allowedCustomerIds || [];
            entity.excludedCustomerIds = this.package.excludedCustomerIds || [];

            if (this.isCreateMode) {
                // Ensure insert, not update
                if (entity.id) {
                    delete entity.id;
                }
                entity._isNew = true;
            }

            return this.packageRepository.save(entity, Shopware.Context.api)
                .then(() => {
                    this.isLoading = false;
                    this.isSaveSuccessful = true;
                    const newId = entity.id || (this.package && this.package.id);
                    this.$router.push(newId
                        ? { name: 'plenty.package.detail', params: { id: newId } }
                        : { name: 'plenty.package.list' });
                })
                .catch((error) => {
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
