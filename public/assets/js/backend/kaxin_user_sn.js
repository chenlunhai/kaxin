define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'kaxin_user_sn/index' + location.search,
                    add_url: 'kaxin_user_sn/add',
                    edit_url: 'kaxin_user_sn/edit',
                    turn_url:'kaxin_user_sn/turn',
                    del_url: 'kaxin_user_sn/del',
                    multi_url: 'kaxin_user_sn/multi',
                    import_url: 'kaxin_user_sn/import',
                    table: 'kaxin_user_sn',
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
                        {field: 'uid', title: __('Uid')},
                        {field: 'snid', title: __('Snid'), operate: 'LIKE'},
                        {field: 'flag', title: __('Flag'),  formatter: function (value, row, index) {
                                                    if(value==1){
                                                         return "已转出";
                                                    }else if(value==2){
                                                        return "转入";
                                                      }else{
                                                          return "自有";
                                                      }
                                                    }},
                        {field: 'addtime', title: __('Addtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'status', title: __('Status'),formatter: function (value, row, index) {                
                                                    if(value==1){
                                                         return "激活状态";
                                                    }else{
                                                        return "未使用";
                                                      }
                                                    }},
                        {field: 'user.username', title: __('User.username'), operate: 'LIKE'},
                        {field: 'user.nickname', title: __('User.nickname'), operate: 'LIKE'},
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