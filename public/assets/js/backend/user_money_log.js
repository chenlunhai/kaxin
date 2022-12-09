define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user_money_log/index' + location.search,
                    add_url: 'user_money_log/add',
                    edit_url: 'user_money_log/edit',
                    del_url: 'user_money_log/del',
                    multi_url: 'user_money_log/multi',
                    import_url: 'user_money_log/import',
                    table: 'user_money_log',
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
                        {field: 'user_id', title: __('User_id')},
                        {field: 'user.username', title: __('User.username'), operate: 'LIKE'},
                        {field: 'user.mobile', title: __('User.mobile'), operate: 'LIKE'},
                        {field: 'money', title: __('Money'), operate: 'BETWEEN'},
                        {field: 'before', title: __('Before'), operate: 'BETWEEN'},
                        {field: 'after', title: __('After'), operate: 'BETWEEN'},
                        {field: 'memo', title: __('Memo'), operate: 'LIKE'},
                        {field: 'type', title: __('Type'), searchList: {"pay": __('Type pay'), "recharge": __('Type recharge'), "withdraw": __('Type withdraw'), "refund": __('Type refund'), "sys": __('Type sys')}, formatter: Table.api.formatter.normal},
                        {field: 'service_ids', title: __('Service_ids'), formatter: function (value, row, index) {
                                                 var a=["系统",'静态释放',"直推奖","间推奖",'流单奖','团队奖','平级奖励','消费','转入','转出','充值'];
                                                 if(value>=0&&value<11){
                                                        return a[value]+"_"+value;
                                                    }else{
                                                        return "购买消费"+"_"+value;
                                                    }
                                                    
                                        }
                                    },
                        {field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime},

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