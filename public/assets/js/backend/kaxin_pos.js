define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'kaxin_pos/index' + location.search,
                    add_url: 'kaxin_pos/add',
                    edit_url: 'kaxin_pos/edit',
                    del_url: 'kaxin_pos/del',
                    multi_url: 'kaxin_pos/multi',
                    import_url: 'kaxin_pos/import',
                    table: 'kaxin_pos',
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
                        {field: 'possn', title: __('Possn'), operate: 'LIKE'},
                        {field: 'flag', title: __('Flag'), formatter: Table.api.formatter.flag},
                              {field: 'wanlshopbrand.name', title: __('name'), },
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