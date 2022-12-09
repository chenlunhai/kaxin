define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'agent_speed/index' + location.search,
                    add_url: 'agent_speed/add',
                    edit_url: 'agent_speed/edit',
                    del_url: 'agent_speed/del',
                    multi_url: 'agent_speed/multi',
                    import_url: 'agent_speed/import',
                    table: 'agent_speed',
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
                        {field: 'user.username', title: __('User.username'), operate: 'LIKE'},
                        {field: 'fromuid', title: __('Fromuid')},
                        {field: 'wanlshoporder.order_no', title: __('Wanlshoporder.order_no'), operate: 'LIKE'},
                        {field: 'money', title: __('Money'), operate: 'BETWEEN'},
                        {field: 'flag', title: __('Flag'), formatter: Table.api.formatter.flag},
                        {field: 'addtime', title: __('Addtime'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime},
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