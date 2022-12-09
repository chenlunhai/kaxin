<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;

/**
 * 充值管理
 *
 * @icon fa fa-circle-o
 */
class RechargeOrder extends Backend {

    /**
     * RechargeOrder模型对象
     * @var \app\common\model\RechargeOrder
     */
    protected $model = null;

    public function _initialize() {
        parent::_initialize();
        $this->model = new \app\common\model\RechargeOrder;
        $this->view->assign("statusList", $this->model->getStatusList());
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

    public function edit($ids = null) {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                if (null !== $this->request->post("province") and null !== $this->request->post("city")) {
                    if ($this->request->post("province") > 0) {
                        $params["agentcode"] = $this->request->post("province");
                    }
                    if ($this->request->post("city") > 0) {
                        $params["agentcode"] = $this->request->post("city");
                    }
                    if ($this->request->post("area") > 0) {
                        $params["agentcode"] = $this->request->post("area");
                    }
                }
                if ($params["status"] == 'paid') {
                    $forLog = new \addons\wanlshop\library\WanlPay\WanlPay();
                    if($params["paytype"]=="usdt"){
                        $price=\app\api\controller\Tools::getConfig1("usdt_price");
                        $money=$params["amount"]*$price;
                    }else{
                         $money=$params["amount"];
                    }
                    
                    $forLog->money($money, $params["user_id"], "用户充值", 'recharge', 10);
                }

                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

}
