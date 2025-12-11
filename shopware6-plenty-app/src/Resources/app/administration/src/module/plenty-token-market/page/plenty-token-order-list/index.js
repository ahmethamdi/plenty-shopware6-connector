import template from './plenty-token-order-list.html.twig';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('plenty-token-order-list', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('listing'),
    ],

    data() {
        return {
            orders: null,
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
        orderRepository() {
            return this.repositoryFactory.create('plenty_token_order');
        },

        columns() {
            return [
                {
                    property: 'createdAt',
                    dataIndex: 'createdAt',
                    label: this.$tc('plenty-token-market.orderList.columnDate'),
                    allowResize: true,
                    primary: true,
                },
                {
                    property: 'customer.firstName',
                    dataIndex: 'customer.firstName,customer.lastName',
                    label: this.$tc('plenty-token-market.orderList.columnCustomer'),
                    allowResize: true,
                },
                {
                    property: 'tokenProduct.name',
                    dataIndex: 'tokenProduct.name',
                    label: this.$tc('plenty-token-market.orderList.columnProduct'),
                    allowResize: true,
                },
                {
                    property: 'tokenAmount',
                    dataIndex: 'tokenAmount',
                    label: this.$tc('plenty-token-market.orderList.columnTokenAmount'),
                    allowResize: true,
                },
                {
                    property: 'status',
                    dataIndex: 'status',
                    label: this.$tc('plenty-token-market.orderList.columnStatus'),
                    inlineEdit: 'string',
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
            criteria.addAssociation('customer');
            criteria.addAssociation('tokenProduct');

            return this.orderRepository.search(criteria, Shopware.Context.api).then((result) => {
                this.orders = result;
                this.total = result.total;
                this.isLoading = false;
            });
        },

        onChangeLanguage() {
            this.getList();
        },
    },
});
