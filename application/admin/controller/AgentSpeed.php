<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 代理奖励
 *
 * @icon fa fa-circle-o
 */
class AgentSpeed extends Backend {

    /**
     * AgentSpeed模型对象
     * @var \app\common\model\AgentSpeed
     */
    protected $model = null;

    public function _initialize() {
        parent::_initialize();
        $this->model = new \app\common\model\AgentSpeed;
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
                    ->with(['user', 'wanlshoporder'])
                    ->where($where)
                    ->order($sort, $order)
                    ->paginate($limit);
            $a = $b = 0;
            foreach ($list as $row) {
                $row["fromuid"] = \app\common\model\User::get($row["fromuid"])["username"];
                $row->getRelation('user')->visible(['username']);
                $row->getRelation('wanlshoporder')->visible(['order_no']);
                if ($row["flag"] == 0) {
                    $a += $row["money"];
                } else {
                    $b += $row["money"];
                }
            }

            $result = array("total" => $list->total(), "rows" => $list->items(),"a"=>$a,"b"=>$b);

            return json($result);
        }
        
        return $this->view->fetch();
    }

    public function del($ids = "") {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ? $ids : $this->request->post("ids");
        if ($ids) {

            dump($ids);
//            $pk = $this->model->getPk();
//            $adminIds = $this->getDataLimitAdminIds();
//            if (is_array($adminIds)) {
//                $this->model->where($this->dataLimitField, 'in', $adminIds);
//            }
//            $list = $this->model->where($pk, 'in', $ids)->select();
//
//            $count = 0;
//            Db::startTrans();
//            try {
//                foreach ($list as $k => $v) {
//                    $count += $v->delete();
//                }
//                Db::commit();
//            } catch (PDOException $e) {
//                Db::rollback();
//                $this->error($e->getMessage());
//            } catch (Exception $e) {
//                Db::rollback();
//                $this->error($e->getMessage());
//            }
//            if ($count) {
//                $this->success();
//            } else {
//                $this->error(__('No rows were deleted'));
//            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

}
