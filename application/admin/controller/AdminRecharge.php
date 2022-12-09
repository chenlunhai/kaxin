<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class AdminRecharge extends Backend {

    /**
     * AdminRecharge模型对象
     * @var \app\admin\model\AdminRecharge
     */
    protected $model = null;

    public function _initialize() {
        parent::_initialize();
        $this->model = new \app\admin\model\AdminRecharge;
        $this->view->assign("dojogList", $this->model->getDojogList());
        $this->view->assign("ctypeList", $this->model->getCtypeList());
    }

    public function import() {
        parent::import();
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 查看
     */
    public function index() {
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = $this->model
                    ->with(['user'])
                    ->where($where)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {

                $row->getRelation('user')->visible(['nickname', 'mobile']);
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    public function add() {

        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $arrays = parent::preExcludeFields($params);

            $uinfo = \think\Db::name("user")->where(["username" => $arrays["username"]])->find();
            if ($uinfo) {
                $user_id = $uinfo["id"];
                $money = $arrays["numbers"];
                $type = "sys";
                $ids = $this->auth->id;
                if ($arrays["dojog"] != 'add') {
                    $money = $arrays["numbers"] * -1;
                }
                if ($arrays["ctype"] == 'money') {
                    \addons\wanlshop\library\WanlPay\WanlPay::money($money, $user_id, "管理员变更余额", $type, 9,$this->auth->id);
                } elseif ($arrays["ctype"] == 'balance') {
                    if($money<0){
                        $this->error(__('积分不可以减少', ''));
                    }else{
                         \addons\wanlshop\library\WanlPay\WanlPay::balance($money, $user_id, "管理员变更积分", $type, $ids);
                         \think\Db::table("static_instance")->insert(["user_id"=>$user_id,"in_num"=>$money,"total_num"=>$money,"remain_num"=>$money,"type"=>9,"create_time"=>time()]);
                    }
                   
                } else {
                    $aa=\addons\wanlshop\library\WanlPay\WanlPay::score($user_id,$money, "管理员变更消费积分", $type, $ids,9);
                    
                }
                $data=[
                    "uid"=>$uinfo["id"],
                    "dojog"=>$arrays["dojog"],
                    "ctype"=>$arrays["ctype"],
                    "numbers"=>$money,
                    "addtime"=>$arrays["addtime"],
                    "operateid"=>$this->auth->id,
                ];
                \app\admin\model\AdminRecharge::create($data);
                $this->success("添加成功");
                
            } else {
                $this->error(__('未找到该用户', ''));
            }
        }
        return $this->view->fetch();
        //return parent::add();
    }

}
