<?php

namespace app\index\controller\wanlshop;

use app\common\controller\Wanlshop;
use think\Db;

/**
 * 发现动态管理
 *
 * @icon fa fa-circle-o
 */
class Find extends Wanlshop
{
    protected $noNeedLogin = '';
    protected $noNeedRight = '*';
    /**
     * Find模型对象
     * @var \app\index\model\wanlshop\Find
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\index\model\wanlshop\Find;
        $this->view->assign("typeList", $this->model->getTypeList());
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
                    ->where($where)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
	
	/**
	 * 添加
	 */
	public function add($type = null)
	{
		//设置过滤方法
		$this->request->filter(['']);
	    if ($this->request->isPost()) {
	        $params = $this->request->post("row/a");
	        if ($params) {
	            $params['shop_id'] = $this->shop->id;
	            $result = false;
	            Db::startTrans();
	            try {
	                $result = $this->model->allowField(true)->save($params);
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
	                $this->error(__('No rows were inserted'));
	            }
	        }
	        $this->error(__('Parameter %s can not be empty', ''));
	    }
	    
		$this->assignconfig("type", $type ? $type : 'new');
		$this->assignconfig("ishead", $type ? false : true);
		return $this->view->fetch();
	}
	
	
	/**
	 * 回收站
	 */
	public function recyclebin()
	{
	    //设置过滤方法
	    $this->request->filter(['strip_tags']);
	    if ($this->request->isAjax()) {
	        list($where, $sort, $order, $offset, $limit) = $this->buildparams();
	        $total = $this->model
	            ->onlyTrashed()
	            ->where($where)
	            ->order($sort, $order)
	            ->count();
	
	        $list = $this->model
	            ->onlyTrashed()
	            ->where($where)
	            ->order($sort, $order)
	            ->limit($offset, $limit)
	            ->select();
	
	        $result = array("total" => $total, "rows" => $list);
	
	        return json($result);
	    }
	    return $this->view->fetch();
	}
	
	
	
	/**
	 * 删除
	 */
	public function del($ids = "")
	{
	    if ($ids) {
	        $pk = $this->model->getPk();
	        $this->model->where('shop_id', '=', $this->shop->id);
	        $list = $this->model->where($pk, 'in', $ids)->select();
	
	        $count = 0;
	        Db::startTrans();
	        try {
	            foreach ($list as $k => $v) {
	                $count += $v->delete();
	            }
	            Db::commit();
	        } catch (PDOException $e) {
	            Db::rollback();
	            $this->error($e->getMessage());
	        } catch (Exception $e) {
	            Db::rollback();
	            $this->error($e->getMessage());
	        }
	        if ($count) {
	            $this->success();
	        } else {
	            $this->error(__('No rows were deleted'));
	        }
	    }
	    $this->error(__('Parameter %s can not be empty', 'ids'));
	}
	
	
	
	/**
	 * 还原
	 */
	public function restore($ids = "")
	{
	    $pk = $this->model->getPk();
	    $this->model->where('shop_id', '=', $this->shop->id);
	    if ($ids) {
	        $this->model->where($pk, 'in', $ids);
	    }
	    $count = 0;
	    Db::startTrans();
	    try {
	        $list = $this->model->onlyTrashed()->select();
	        foreach ($list as $index => $item) {
	            $count += $item->restore();
	        }
	        Db::commit();
	    } catch (PDOException $e) {
	        Db::rollback();
	        $this->error($e->getMessage());
	    } catch (Exception $e) {
	        Db::rollback();
	        $this->error($e->getMessage());
	    }
	    if ($count) {
	        $this->success();
	    }
	    $this->error(__('No rows were updated'));
	}
	
	
}
