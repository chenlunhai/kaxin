define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'admin_recharge/index' + location.search,
                    add_url: 'admin_recharge/add',
                    edit_url: 'admin_recharge/edit',
                    del_url: 'admin_recharge/del',
                    multi_url: 'admin_recharge/multi',
                    import_url: 'admin_recharge/import',
                    table: 'admin_recharge',
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
                        {field: 'uid', title: __('Uid')},   {field: 'user.nickname', title: __('User.nickname'), operate: 'LIKE'},
                        {field: 'user.mobile', title: __('User.mobile'), operate: 'LIKE'},
                        
                        {field: 'dojog', title: __('Dojog'), searchList: {"add":__('Add'),"reduce":__('减少')}, formatter: Table.api.formatter.normal},
                        {field: 'ctype', title: __('Ctype'), searchList: {"money":__('Money'),"balance":__('积分'),"score":__('消费积分')}, formatter: Table.api.formatter.normal},
                        {field: 'numbers', title: __('Numbers'), operate:'BETWEEN'},
                        {field: 'addtime', title: __('Addtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                       
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