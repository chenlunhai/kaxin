define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/user/index',
                    add_url: 'user/user/add',
                    edit_url: 'user/user/edit',
                    del_url: 'user/user/del',
                    multi_url: 'user/user/multi',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'user.id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true},
                        {field: 'username', title: __('Username'), operate: 'LIKE'},
                        {field: 'mobile', title: __('Mobile'), operate: 'LIKE'},
                        {field: 'truename', title: __('推荐人'), operate: 'LIKE'},
                        {field: 'avatar', title: __('Avatar'), events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false},
                        {field: 'level', title: __('Level'), operate: 'BETWEEN', sortable: true, formatter: function (value, row, index) {
                                return "V" + value;
                            }},
                        {field: 'gender', title: __('Gender'), visible: false, searchList: {1: __('Male'), 0: __('Female')}},
                        {field: 'score', title: __('消费积分'), operate: 'BETWEEN', sortable: true},
                        {field: 'money', title: __('money'), operate: 'BETWEEN', sortable: true},
                        {field: 'balance', title: __('积分')},
                        {field: 'successions', title: __('Successions'), visible: false, operate: 'BETWEEN', sortable: true},
                        {field: 'maxsuccessions', title: __('Maxsuccessions'), visible: false, operate: 'BETWEEN', sortable: true},
//                        {field: 'logintime', title: __('Logintime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
//                        {field: 'truename', title: __('truename'), operate: 'LIKE'},
//                        {field: 'cardnum', title: __('cardnum'), operate: 'LIKE'},
                        {field: 'flag', title: __('flag'), formatter: Table.api.formatter.status, searchList: {1: __('通过'), 0: __('否'), 2: __('待审核')}},
//                        {field: 'jointime', title: __('Jointime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
//                        {field: 'joinip', title: __('Joinip'), formatter: Table.api.formatter.search},
                        {field: 'status', title: __('Status'), formatter: Table.api.formatter.status, searchList: {normal: __('Normal'), hidden: __('Hidden')}},
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
        reftree: function () {
            Controller.api.bindevent();
            var createNode = function (list) {
                var html = '';
                list.forEach(function (item) {
                    html += '<div style="padding-left: 30px;" data-id="' + item.id + '">';
                    html += '<table>';
                    html += '<tr>';
                    if (item.team > 0) {
                        html += '<td><span class="addTable">+</span> <span class="subTable">-</span> <span class="username">' + item.username + '(' + item.level + ')</span></td>';
                    } else {
                        html += '<td><span class="leaf">+</span><span class="username">' + item.username + '(' + item.level + ')</span></td>';
                    }
                    html += '<td>团队人数：' + item.team + '</td>';
                    html += '<td>余额:' + item.money + '</td>';
                    html += '<td>积分:' + item.balance + '</td>';
                    html += '<td>消费积分:' + item.score + '</td>';
                    html += '<td>团队业绩:' + item.teamorder + '</td>';
                    html += '</tr>';
                    html += '</table>';
                    html += '</div>';
                });
                return html;
            };
            var getList = function (uid, pid, target) {
                if (!uid) {
                    uid = 0;
                }
                if (!pid) {
                    pid = 0;
                }
                $.post('user/user/reftree', {user_id: uid, pid: pid}, function (json) {
                    if (json.code === 200) {
                        var html = createNode(json.list);
                        if (target === 0) {
                            $("#data-box").html(html);
                        } else {
                            target.parents('div').first().append(html);
                            target.hide();
                            target.next().css("display", "inline-block");
                        }
                    }
                }, 'json');
            };
            $(function () {
                getList(0, 0, 0);
            });
            $("#data-box").on("click", ".addTable", function () {
                var pid = $(this).parents('div').first().data("id");
                getList(0, pid, $(this));
            });
            $("#data-box").on("click", ".subTable", function () {
                $(this).parents("table").first().nextAll().remove();
                $(this).hide();
                $(this).prev().css("display", "inline-block");
            });

            $("#searchUserId").click(function () {
                var user_id = $("#userId").val();
                if (!user_id) {
                    Toastr.error("请输入用户ID");
                    return false;
                }
                getList(user_id, 0, 0);
            });
        },
        markettree: function () {
            Controller.api.bindevent();
            var createNode = function (list) {
                var html = '';
                var k = 1;
                var j = 0;
                list.forEach(function (item) {
                    j = 0;
                    item.forEach(function (v) {
                        html = '';
                        if (v.hasOwnProperty('id')) {
                            html += '<table cellPadding="0" cellSpacing="0" width="100%" border="1">';
                            html += '<thead>';
                            html += '<tr>';
                            html += '<th colSpan="2">id:' + v.id + '</th>';
                            html += '</tr>';
                            html += '</thead>';
                            html += '<tbody>';
                            html += '<tr>';
                            html += '<td colSpan="2"><span class="touser" data-id="' + v.id + '">' + v.username + '</span>' + ((k < 2) ? '<span class="toparent" data-id="' + v.pid + '">上级</span>' : '') + '</td>';
                            html += '</tr>';
                            html += '<tr>';
                            html += '<td colSpan="2">入驻时间：' + v.ctime + '</td>';
                            html += '</tr>';
                            html += '<tr>';
                            html += '<td colSpan="2">首单时间：' + v.otime + '</td>';
                            html += '</tr>';
                            html += '<tr>';
                            html += '</tr>';
                            html += '<tr>';
                            html += '<td>一部</td>';
                            html += '<td>二部</td>';
                            html += '</tr>';
                            html += '<tr>';
                            html += '<td>累计pv：' + (v.left.hasOwnProperty('orders') ? v.left.orders : 0) + '</td>';
                            html += '<td>累计pv：' + (v.right.hasOwnProperty('orders') ? v.right.orders : 0) + '</td>';
                            html += '</tr>';
                            html += '<tr>';
                            html += '<td>有效pv：' + (v.left.hasOwnProperty('pv') ? v.left.pv : 0) + '</td>';
                            html += '<td>有效pv：' + (v.right.hasOwnProperty('pv') ? v.right.pv : 0) + '</td>';
                            html += '</tr>';
                            html += '<tr>';
                            html += '<td>人数：' + (v.left.hasOwnProperty('team') ? v.left.team : 0) + '</td>';
                            html += ' <td>人数：' + (v.right.hasOwnProperty('team') ? v.right.team : 0) + '</td>';
                            html += '</tr>';
                            html += '</tbody>';
                            html += '</table>';
                        }
                        $("#lv-" + k + "-" + j).html(html);
                        j = j + 1;
                    });
                    k = k + 1;
                });
                return html;
            };
            var getList = function (uid, pid) {
                if (!uid) {
                    uid = 0;
                }
                if (!pid) {
                    pid = 0;
                }
                $.post('user/markettree', {user_id: uid, pid: pid}, function (json) {
                    if (json.code === 200) {
                        createNode(json.list);
                    }
                }, 'json');
            };
            $(function () {
                getList(0, 0);
            });

            $("#searchUserId").click(function () {
                var user_id = $("#userId").val();
                if (!user_id) {
                    Toastr.error("请输入用户ID");
                    return false;
                }
                getList(user_id, 0);
            });

            $(".market-tree").on("click", ".touser", function () {
                getList($(this).data("id"), 0);
            });
            $(".market-tree").on("click", ".toparent", function () {
                getList($(this).data("id"), 0);
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});