define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user_score_log/index' + location.search,
                    add_url: 'user_score_log/add',
                    edit_url: 'user_score_log/edit',
                    del_url: 'user_score_log/del',
                    multi_url: 'user_score_log/multi',
                    import_url: 'user_score_log/import',
                    table: 'user_score_log',
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
                        {field: 'score', title: __('Score'), operate: 'BETWEEN'},
                        {field: 'before', title: __('Before'), operate: 'BETWEEN'},
                        {field: 'after', title: __('After'), operate: 'BETWEEN'},
                        {field: 'memo', title: __('Memo'), operate: 'LIKE'},
                        {field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime},
                        {field: 'source', title: __('Source'),formatter: function (value, row, index) {
                                                 var a=["系统",'静态释放',"直推奖","间推奖",'流单奖','团队奖','平级奖励','消费'];
                                                    if(value==9){
                                                         return a[0]+"_"+value;
                                                    }else if(value>=0&&value<8){
                                                        return a[value]+"_"+value;
                                                    }else{
                                                        return "待定"+"_"+value;
                                                    }
                                                    
                                        }
                        
                        
                        },
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