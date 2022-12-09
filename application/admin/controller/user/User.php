<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use app\common\library\Auth;
use think\Db;

/**
 * 会员管理
 *
 * @icon fa fa-user
 */
class User extends Backend {

    protected $relationSearch = true;
    protected $searchFields = 'id,username,nickname';

    /**
     * @var \app\admin\model\User
     */
    protected $model = null;

    public function _initialize() {
        parent::_initialize();
        $this->model = model('User');
    }

    /**
     * 查看
     */
    public function index() {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $list = $this->model
                    ->with('group')
                    ->where($where)
                    ->order($sort, $order)
                    ->paginate($limit);
            // $vip = Db::name("level_config")->select();
            foreach ($list as $k => $v) {

                $pinfo = Db::name("user")->where(["id" => $v->pid])->find();
                if ($pinfo["pid"] == 0) {
                    $v->truename = "系统";
                } else {
                    $v->truename = $pinfo["username"];
                }
                $v->avatar = $v->avatar ? cdnurl($v->avatar, true) : letter_avatar($v->nickname);
                // foreach ($vip as $key => $value) {
                //     if ($value["level"] == $v->level) {
                //         $v["level"] = $value["lname"];
                //         break;
                //     }
                // }
                $v->hidden(['password', 'salt']);
            }
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    public function agent() {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            $uid = $this->request->request("uid");
            $getMoney = Db::name("agent_speed")->where(["uid" => $uid, "flag" => 0])->sum("money") ?? 0;
            if ($getMoney == 0) {
                $result = ["code" => 1, "msg" => "可结算金额为0！"];
            } else {
                $teamwork = new \app\api\controller\wanlshop\Teamwork();
                $user = \app\common\model\User::get($uid);
                Db::name("agent_speed")->where(["uid" => $uid, "flag" => 0])->update(["flag" => 1]);
                $result = $teamwork->bonus($user, $getMoney, "代理加速(sys)", 99);
                if ($result) {

                    $result = ["code" => 0, "msg" => "结算成功！"];
                } else {
                    $result = ["code" => 1, "msg" => "结算异常！"];
                }
            }
            return json($result);
        }

        if ($this->request->Get()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $list = $this->model
                    ->with('group')
                    ->where($where)
                    ->order($sort, $order)
                    ->paginate($limit);
            foreach ($list as $k => $v) {
                $v->avatar = $v->avatar ? cdnurl($v->avatar, true) : letter_avatar($v->nickname);
                $v->hidden(['password', 'salt']);
                if ($v["agentcode"] == 0) {
                    unset($list[$k]);
                } else {
                    $list[$k]["agentgrade"] = Db::name("area")->where(["id" => $v["agentcode"]])->find()["level"];
                    if ($list[$k]["agentgrade"] < 3) {
                        $list[$k]["agent"] = Db::name("area")->where(["id" => $v["agentcode"]])->field("name")->find()["name"];
                        $list[$k]["checked"] = Db::name("agent_speed")->where(["uid" => $v["id"], "flag" => 1])->sum("money") ?? 0;
                        $list[$k]["uncheck"] = Db::name("agent_speed")->where(["uid" => $v["id"], "flag" => 0])->sum("money") ?? 0;
                    } else {
                        unset($list[$k]);
                    }
                }
            }
            $result = array("total" => $list->total(), "rows" => $list->items());
            $this->assign("result", $result);
            return $this->view->fetch();
        }
    }

    /**
     * 添加
     */
    public function add() {
        if ($this->request->isPost()) {
            $this->token();
        }
        return parent::add();
    }

    /**
     * 编辑
     */
    public function edit($ids = null) {
        if ($this->request->isPost()) {
            $this->token();
        }
        $row = $this->model->get($ids);
        $getAreaInfo = \app\admin\model\Area::get($row["agentcode"]);
        if ($getAreaInfo) {
            $name = $getAreaInfo["name"];
        } else {
            $name = "否";
        }
        $this->modelValidate = true;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $this->view->assign("agent", $name);
        $this->view->assign('groupList', build_select('row[group_id]', \app\admin\model\UserGroup::column('id,name'), $row['group_id'], ['class' => 'form-control selectpicker']));
        return parent::Useredit($ids);
    }

    /**
     * 删除
     */
    public function del($ids = "") {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ? $ids : $this->request->post("ids");
        $row = $this->model->get($ids);
        $this->modelValidate = true;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        Auth::instance()->delete($row['id']);
        $this->success();
    }

    public function reftree() {
        if ($this->request->isAjax()) {
            $where = [];
            $pid = input("id/d", 0);
            $flag = input("flag/d", 0);
            if ($pid != 0 and $flag == 0) {
                $where['pid'] = 0;
            }
             if ($pid >1 and $flag == 0) {
                $where['pid'] =$pid;
            }
            if ($pid != 0 and $flag == 1) {
                $where['id'] = $pid;
            }
            $list = Db::name("user")
                    ->where($where)
                    ->select();

            $data = [];

            foreach ($list as $key => $val) {
                $totals = Db::name("user_team")->where(["uid" => $val["id"]])->find();
                $data[$key]['text'] = "用户ID：" . $val["id"] . "__等级：" . $val["level"] . "__用户名：" . $val["nickname"] . "___团队人数:<i style=\"color:red\">" . $totals["teamnum"] . "</i>___团队业绩：" . $totals["teamorder"] . "__個人业绩:" . $totals["own_money"] . "___手机号" . $val["mobile"];
                $data[$key]["children"] = true;
                $data[$key]["id"] = $val["id"];
            }

//           / dump($list);
            return json($data);
        } else {
            return$this->view->fetch();
        }
    }

    public function search() {
        if ($this->request->isAjax()) {
            $where = [];
            $userId = input("user_id/s", 0);
            $pid = input("id/d", 0);
            if ($pid) {
                $where['pid'] = $pid;
            }
            if ($pid != 0) {
                $where['pid'] = $pid;
            }
            if ($pid == 1 and $userId == 0) {
                $where['pid'] = 0;
            }
            if ($userId != 0) {
                $list = Db::name("user")
                        ->where("id=" . $userId . " or username ='$userId'")
                        ->select();
            } else {
                $list = Db::name("user")
                        ->where($where)
                        ->select();
            }

            $data = [];

            foreach ($list as $key => $val) {
                $totals = Db::name("user_team")->where(["uid" => $val["id"]])->find();
                $data[$key]['text'] = "用户ID：" . $val["id"] . "__等级：" . $val["level"] . "__用户名：" . $val["nickname"] . "___团队人数:<i style=\"color:red\">" . $totals["teamnum"] . "</i>___团队业绩：" . $totals["teamorder"] . "__個人业绩:" . $totals["own_money"] . "___手机号" . $val["mobile"];
                $data[$key]["children"] = true;
                $data[$key]["id"] = $val["id"];
            }

//           / dump($list);
            return json($data);
        }
    }

    public function markettree() {
        if ($this->request->isAjax()) {
            $userId = input("user_id/s", 0);
            $pid = input("pid/d", 0);
            if (!$userId && !$pid) {
                $where['pid'] = 0;
            } else {
                if ($userId) {
                    if (is_numeric($userId) && strlen($userId) < 11) {
                        $where['id'] = $userId;
                    } else {
                        $where['username'] = $userId;
                    }
                }
                if ($pid) {
                    $where['pid'] = $pid;
                }
            }
            $node = Db::name("user_market_tree_view")
                    ->where($where)
                    ->find();
//            $dayuse = Db::name("site")->where("id", 1)->value("bouns_day");
            // 第一层
            $list[0][] = [
                'id' => $node['id'],
                'username' => $node['username'],
                'pv' => $node['pv'],
                'pid' => $node['pid'],
                'ctime' => $this->getRegisterTime($node['id']),
                'otime' => $this->getOrderTime($node['id']),
                'level' => $this->getLevel($node['level']),
                'left' => $this->marketAlanyze($node['left_node_id'], $node['left_pv'], $node['left_total_pv']),
                'right' => $this->marketAlanyze($node['right_node_id'], $node['right_pv'], $node['right_total_pv']),
            ];
            // 第二层
            if ($node['left_node_id']) {
                $node2 = Db::name("user_market_tree_view")
                        ->where("id", $node['left_node_id'])
                        ->find();
                $list[1][] = [
                    'id' => $node2['id'],
                    'username' => $node2['username'],
                    'pv' => $node2['pv'],
                    'pid' => $node2['pid'],
                    'ctime' => $this->getRegisterTime($node2['id']),
                    'otime' => $this->getOrderTime($node2['id']),
                    'level' => $this->getLevel($node2['level']),
                    'left' => $this->marketAlanyze($node2['left_node_id'], $node2['left_pv'], $node2['left_total_pv']),
                    'right' => $this->marketAlanyze($node2['right_node_id'], $node2['right_pv'], $node2['right_total_pv']),
                ];
                if ($node2['left_node_id']) {
                    $node3 = Db::name("user_market_tree_view")
                            ->where("id", $node2['left_node_id'])
                            ->find();
                    $list[2][] = [
                        'id' => $node3['id'],
                        'username' => $node3['username'],
                        'pv' => $node3['pv'],
                        'pid' => $node3['pid'],
                        'ctime' => $this->getRegisterTime($node3['id']),
                        'otime' => $this->getOrderTime($node3['id']),
                        'level' => $this->getLevel($node3['level']),
                        'left' => $this->marketAlanyze($node3['left_node_id'], $node3['left_pv'], $node3['left_total_pv']),
                        'right' => $this->marketAlanyze($node3['right_node_id'], $node3['right_pv'], $node3['right_total_pv']),
                    ];
                } else {
                    $list[2][] = [];
                }
                if ($node2['right_node_id']) {
                    $node3 = Db::name("user_market_tree_view")
                            ->where("id", $node2['right_node_id'])
                            ->find();
                    $list[2][] = [
                        'id' => $node3['id'],
                        'username' => $node3['username'],
                        'pv' => $node3['pv'],
                        'pid' => $node3['pid'],
                        'ctime' => $this->getRegisterTime($node3['id']),
                        'otime' => $this->getOrderTime($node3['id']),
                        'level' => $this->getLevel($node3['level']),
                        'left' => $this->marketAlanyze($node3['left_node_id'], $node3['left_pv'], $node3['left_total_pv']),
                        'right' => $this->marketAlanyze($node3['right_node_id'], $node3['right_pv'], $node3['right_total_pv']),
                    ];
                } else {
                    $list[2][] = [];
                }
            } else {
                $list[1][] = [];
                $list[2][] = [];
                $list[2][] = [];
            }

            if ($node['right_node_id']) {
                $node2 = Db::name("user_market_tree_view")
                        ->where("id", $node['right_node_id'])
                        ->find();
                $list[1][] = [
                    'id' => $node2['id'],
                    'username' => $node2['username'],
                    'pv' => $node2['pv'],
                    'pid' => $node2['pid'],
                    'ctime' => $this->getRegisterTime($node2['id']),
                    'otime' => $this->getOrderTime($node2['id']),
                    'level' => $this->getLevel($node2['level']),
                    'left' => $this->marketAlanyze($node2['left_node_id'], $node2['left_pv'], $node2['left_total_pv']),
                    'right' => $this->marketAlanyze($node2['right_node_id'], $node2['right_pv'], $node2['right_total_pv']),
                ];
                if ($node2['left_node_id']) {
                    $node3 = Db::name("user_market_tree_view")
                            ->where("id", $node2['left_node_id'])
                            ->find();
                    $list[2][] = [
                        'id' => $node3['id'],
                        'username' => $node3['username'],
                        'pv' => $node3['pv'],
                        'pid' => $node3['pid'],
                        'ctime' => $this->getRegisterTime($node3['id']),
                        'otime' => $this->getOrderTime($node3['id']),
                        'level' => $this->getLevel($node3['level']),
                        'left' => $this->marketAlanyze($node3['left_node_id'], $node3['left_pv'], $node3['left_total_pv']),
                        'right' => $this->marketAlanyze($node3['right_node_id'], $node3['right_pv'], $node3['right_total_pv']),
                    ];
                } else {
                    $list[2][] = [];
                }
                if ($node2['right_node_id']) {
                    $node3 = Db::name("user_market_tree_view")
                            ->where("id", $node2['right_node_id'])
                            ->find();
                    $list[2][] = [
                        'id' => $node3['id'],
                        'username' => $node3['username'],
                        'pv' => $node3['pv'],
                        'pid' => $node3['pid'],
                        'ctime' => $this->getRegisterTime($node3['id']),
                        'otime' => $this->getOrderTime($node3['id']),
                        'level' => $this->getLevel($node3['level']),
                        'left' => $this->marketAlanyze($node3['left_node_id'], $node3['left_pv'], $node3['left_total_pv']),
                        'right' => $this->marketAlanyze($node3['right_node_id'], $node3['right_pv'], $node3['right_total_pv']),
                    ];
                } else {
                    $list[2][] = [];
                }
            } else {
                $list[1][] = [];
                $list[2][] = [];
                $list[2][] = [];
            }
            return json(['code' => 200, 'msg' => 'ok', 'list' => $list]);
        } else {
            return $this->fetch();
        }
    }

}
