import template from './plenty-package-list.html.twig';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('plenty-package-list', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('listing'),
    ],

    data() {
        return {
            packages: null,
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
        packageRepository() {
            return this.repositoryFactory.create('plenty_package');
        },

        columns() {
            return [
                {
                    property: 'name',
                    dataIndex: 'name',
                    label: this.$tc('plenty-package.list.columnName'),
                    routerLink: 'plenty.package.detail',
                    inlineEdit: 'string',
                    allowResize: true,
                    primary: true,
                },
                {
                    property: 'targetAmount',
                    dataIndex: 'targetAmount',
                    label: this.$tc('plenty-package.list.columnTargetAmount'),
                    inlineEdit: 'number',
                    allowResize: true,
                },
                {
                    property: 'tokenReward',
                    dataIndex: 'tokenReward',
                    label: this.$tc('plenty-package.list.columnTokenReward'),
                    inlineEdit: 'number',
                    allowResize: true,
                },
                {
                    property: 'active',
                    dataIndex: 'active',
                    label: this.$tc('plenty-package.list.columnActive'),
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

            return this.packageRepository.search(criteria, Shopware.Context.api)
                .then((result) => {
                    this.packages = result;
                    this.total = result.total;
                    this.isLoading = false;
                    return result;
                })
                .catch((error) => {
                    console.error('Error loading packages:', error);
                    this.isLoading = false;
                    this.packages = [];
                    this.total = 0;
                });
        },

        onChangeLanguage() {
            this.getList();
        },
    },
});
