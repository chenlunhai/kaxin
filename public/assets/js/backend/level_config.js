define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'level_config/index' + location.search,
                    add_url: 'level_config/add',
                    edit_url: 'level_config/edit',
                    del_url: 'level_config/del',
                    multi_url: 'level_config/multi',
                    import_url: 'level_config/import',
                    table: 'level_config',
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
                        {field: 'level', title: __('Level')},
                        {field: 'min_money', title: __('Min_money'), operate:'BETWEEN'},
                        {field: 'max_money', title: __('Max_money'), operate:'BETWEEN'},
                        {field: 'rate1', title: __('Rate1'), operate:'BETWEEN'},
                        {field: 'rate2', title: __('Rate2'), operate:'BETWEEN'},
                        {field: 'lname', title: __('Lname'), operate: 'LIKE'},
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