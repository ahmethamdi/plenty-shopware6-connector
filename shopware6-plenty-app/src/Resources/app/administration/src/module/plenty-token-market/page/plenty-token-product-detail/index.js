import template from './plenty-token-product-detail.html.twig';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;
const { mapPropertyErrors } = Shopware.Component.getComponentHelper();

Component.register('plenty-token-product-detail', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('notification'),
    ],

    data() {
        return {
            product: null,
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
        productRepository() {
            return this.repositoryFactory.create('plenty_token_product');
        },

        ...mapPropertyErrors('product', [
            'name',
            'tokenPrice',
        ]),

        isCreateMode() {
            return this.$route.name === 'plenty.token.market.product-create';
        },
    },

    created() {
        this.loadEntityData();
    },

    methods: {
        loadEntityData() {
            this.isLoading = true;

            if (this.isCreateMode) {
                this.product = this.productRepository.create(Shopware.Context.api);
                this.product.active = true;
                this.product.stock = 0;
                this.isLoading = false;
                return;
            }

            const criteria = new Criteria();

            this.productRepository.get(this.$route.params.id, Shopware.Context.api, criteria)
                .then((entity) => {
                    this.product = entity;
                    this.isLoading = false;
                });
        },

        onSave() {
            this.isLoading = true;

            return this.productRepository.save(this.product, Shopware.Context.api)
                .then(() => {
                    this.isLoading = false;
                    this.isSaveSuccessful = true;
                    this.$router.push({ name: 'plenty.token.market.product-detail', params: { id: this.product.id } });
                })
                .catch(() => {
                    this.isLoading = false;
                    this.createNotificationError({
                        message: this.$tc('plenty-token-market.productDetail.errorMessage'),
                    });
                });
        },

        onCancel() {
            this.$router.push({ name: 'plenty.token.market.product-list' });
        },
    },
});
