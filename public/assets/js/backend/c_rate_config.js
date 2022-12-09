define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'c_rate_config/index' + location.search,
                    add_url: 'c_rate_config/add',
                    edit_url: 'c_rate_config/edit',
                    del_url: 'c_rate_config/del',
                    multi_url: 'c_rate_config/multi',
                    import_url: 'c_rate_config/import',
                    table: 'c_rate_config',
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
                        {field: 'staticrate', title: __('Staticrate'), operate:'BETWEEN'},
                        {field: 'recommendrate', title: __('Recommendrate'), operate: 'LIKE'},
                        {field: 'sharerate', title: __('Sharerate'), operate: 'LIKE'},
                        {field: 'agentrate', title: __('Agentrate'), operate: 'LIKE'},
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