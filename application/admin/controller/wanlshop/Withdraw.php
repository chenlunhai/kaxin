<?php

namespace app\admin\controller\wanlshop;

use app\common\controller\Backend;
use think\Db;

/**
 * 提现管理
 *
 * @icon fa fa-circle-o
 */
class Withdraw extends Backend {

    /**
     * Withdraw模型对象
     * @var \app\admin\model\wanlshop\Withdraw
     */
    protected $model = null;

    public function _initialize() {
        parent::_initialize();
        $this->model = new \app\admin\model\wanlshop\Withdraw;
        $this->view->assign("statusList", $this->model->getStatusList());
    }

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

            $list = $this->model->with(["user"])
                    ->where($where)
                    ->order($sort, $order)
                    ->paginate($limit);
            foreach ($list as $row) {
                $row->getRelation('user')->visible(['username', 'mobile']);
                
               $user= Db::name("wanlshop_pay_account")->where(["user_id"=>$row["user_id"]])->find();
                $row->memo=$user["username"];
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 详情
     */
    public function detail($ids = null) {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 同意
     */
    public function agree($ids = null) {
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
        if ($row['status'] == 'successed') {
            $this->error(__('已审核过本店铺，请不要重复审核！'));
        }
        if ($this->request->isPost()) {
            $result = false;
            Db::startTrans();
            try {
                //是否采用模型验证
                if ($this->modelValidate) {
                    $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                    $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                    $row->validateFailException(true)->validate($validate);
                }
                // 审核通过
                // 
                Db::name("wanlshop_order")->where(["id" => $row->fromorder])->update(["state" =>'8']);
//                $servicefee = $row->taxes;
//                \app\common\model\User::score($servicefee, $row->user_id, '提现增加10%积分');
//                $wiInfo = Db::name("withdraw")->where(["id" => $row['id']])->find();
//                $u = \app\common\model\User::get($wiInfo["user_id"]);
//                Db::name("user")->where(["id" => $wiInfo["user_id"]])->update(["buycoupon" => $u["buycoupon"] + $wiInfo["taxes"]]);
                $result = $row->allowField(true)->save([
                    'status' => 'successed',
                    'transfertime' => time()
                ]);
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
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 拒绝
     */
    public function refuse($ids = null) {
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

                    $params['status'] = 'rejected';
                    $wiInfo = Db::name("withdraw")->where(["id" => $row['id']])->find();
                    $result = $row->allowField(true)->save($params);
                    //if ($wiInfo['type'] == "USDT") {
                    //controller('addons\wanlshop\library\WanlPay\WanlPay')->money(+$row['money'], $row['user_id'], '提现拒绝返回USDT', 'withdraw', $row['id']);
                    //  } else {
                    // 更新用户金额
                    $money = $row['money'] + $wiInfo["handingfee"] + $wiInfo["taxes"];
                    controller('addons\wanlshop\library\WanlPay\WanlPay')->money(+$money, $row['user_id'], '系統驳回', 'withdraw', $row['id']);
                    // \app\common\model\User::score(-$wiInfo["handingfee"], $wiInfo["user_id"], '拒绝提现返回积分');
                    // Db::name("user")->where(["id" => $wiInfo["user_id"]])->update(["buycoupon" => \app\common\model\User::get($wiInfo["user_id"])["buycoupon"] - $wiInfo["taxes"]]);
                    //}
                    //SELECT * FROM `xsh_withdraw` WHERE  `id` = 5 LIMIT 1

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
