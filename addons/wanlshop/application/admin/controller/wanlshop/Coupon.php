<?php

namespace app\admin\controller\wanlshop;

use app\common\controller\Backend;

/**
 * 卡券管理
 *
 * @icon fa fa-circle-o
 */
class Coupon extends Backend
{
    
    /**
     * Coupon模型对象
     * @var \app\admin\model\wanlshop\Coupon
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\wanlshop\Coupon;
        $this->view->assign("typeList", $this->model->getTypeList());
        $this->view->assign("usertypeList", $this->model->getUsertypeList());
        $this->view->assign("rangetypeList", $this->model->getRangetypeList());
        $this->view->assign("pretypeList", $this->model->getPretypeList());
    }

    public function import()
    {
        parent::import();
    }

    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                    ->with(['shop'])
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->with(['shop'])
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                
                $row->getRelation('shop')->visible(['shopname']);
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
}
