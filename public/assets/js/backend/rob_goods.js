define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'rob_goods/index' + location.search,
                    add_url: 'rob_goods/add',
                    edit_url: 'rob_goods/edit',
                    del_url: 'rob_goods/del',
                    multi_url: 'rob_goods/multi',
                    import_url: 'rob_goods/import',
                    table: 'rob_goods',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'wanlshopgoods.title', title: __('Wanlshopgoods.title'), operate: 'LIKE'},
                        {field: 'starttime', title: __('Starttime'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime},

                        {field: 'buymax', title: __('Buymax')},
                        {field: 'buylimit', title: __('Buylimit')},
                        {field: 'times', title: __('Times')},
                        {field: 'joinnum', title: __('Joinnum')},
                        {field: 'getnum', title: __('Getnum')},
                        {field: 'hadjoin', title: __('Hadjoin')},
                        {field: 'rsort', title: __('Rsort')},
                        {field: 'is_delete', title: __('Is_delete'), searchList: {"yes": __('Yes'), "no": __('No')}, formatter: Table.api.formatter.normal},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});