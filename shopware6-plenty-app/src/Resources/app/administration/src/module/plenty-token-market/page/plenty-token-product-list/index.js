import template from './plenty-token-product-list.html.twig';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('plenty-token-product-list', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('listing'),
    ],

    data() {
        return {
            products: null,
            isLoading: false,
            total: 0,
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

        columns() {
            return [
                {
                    property: 'name',
                    dataIndex: 'name',
                    label: this.$tc('plenty-token-market.productList.columnName'),
                    routerLink: 'plenty.token.market.product-detail',
                    inlineEdit: 'string',
                    allowResize: true,
                    primary: true,
                },
                {
                    property: 'tokenPrice',
                    dataIndex: 'tokenPrice',
                    label: this.$tc('plenty-token-market.productList.columnTokenPrice'),
                    inlineEdit: 'number',
                    allowResize: true,
                },
                {
                    property: 'stock',
                    dataIndex: 'stock',
                    label: this.$tc('plenty-token-market.productList.columnStock'),
                    inlineEdit: 'number',
                    allowResize: true,
                },
                {
                    property: 'active',
                    dataIndex: 'active',
                    label: this.$tc('plenty-token-market.productList.columnActive'),
                    inlineEdit: 'boolean',
                    allowResize: true,
                },
            ];
        },
    },

    created() {
        this.getList();
    },

    methods: {
        getList() {
            this.isLoading = true;

            const criteria = new Criteria(this.page, this.limit);
            criteria.addSorting(Criteria.sort('createdAt', 'DESC'));

            return this.productRepository.search(criteria, Shopware.Context.api).then((result) => {
                this.products = result;
                this.total = result.total;
                this.isLoading = false;
            });
        },

        onChangeLanguage() {
            this.getList();
        },
    },
});
