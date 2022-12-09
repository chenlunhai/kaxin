<?php
// 2020年2月17日22:14:37
// 2020年5月1日03:37:34
namespace app\index\controller\wanlshop;

use app\common\controller\Wanlshop;
use think\Db;
use Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use fast\Tree;
use fast\Random;

/**
 * 产品管理
 * @internal
 */
class Page extends Wanlshop
{
    protected $noNeedLogin = '';
    protected $noNeedRight = '*';
    /**
     * Page模型对象
     */
    protected $model = null;
    
    
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\index\model\wanlshop\Page;
        $this->view->assign("typeList", $this->model->getTypeList());
		$this->view->assign("sortList", $this->model->getSortList());
        $this->view->assign("statusList", $this->model->getStatusList());
        $tree = Tree::instance();
        $category = new \app\index\model\wanlshop\Category;// 类目
        $tree->init(collection($category->where(['type' => 'goods'])->order('weigh desc,id desc')->field('id,pid,type,name,name_spacer')->select())->toArray(), 'pid');
        $this->assignconfig('pageCategory', $tree->getTreeList($tree->getTreeArray(0), 'name_spacer'));
    }
    
    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
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
     * 编辑
     */
    public function edit($ids = null)
    {
        if ($this->request->isPost()) {
            $params = $this->request->post();
            if ($params) {
                if (!array_key_exists("item", $params)) {
                    $this->error('请编辑页面后再发布');
                }
                $this->model->shop_id = $this->shop->id;
                $this->model->name = $params['name'];
                $this->model->page_token = $params['page_token'];
                $this->model->type = $params['type'];
                $this->model->page = json_encode($params['page']);
                $this->model->item = json_encode($params['item']);
                $this->model->save();
                $page_token = $this->model->page_token;
                $id = $this->model->id;
                // 把除此之外的数据全部扔进回收站
                $list = $this->model->where(['page_token' => $page_token])->select();
                
                foreach ($list as $vo) {
                    if ($vo->id != $id) {
                        $this->model->destroy($vo->id);
                    }
                }
                // 查询最新历史状态
                $recover = $this->model->onlyTrashed()->where(['page_token'=> $page_token])->select();
                $this->success("发布并保存成功", "url", $recover);
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ($row['shop_id'] !=$this->shop->id) {
            $this->error(__('You have no permission'));
        }
        $this->assignconfig('page', $row);
        $recover = $this->model->onlyTrashed()->where(['page_token' => $row['page_token']])->select();
        $this->assignconfig('pageRecover', $recover);
        return $this->view->fetch();
    }
    
    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params['shop_id'] = $this->shop->id;
                $result = false;
                Db::startTrans();
                try {
                    if ($params['type'] == 'index') {
                        $this->error('非法操作');
                    }
                    if ($params['type'] == 'shop') {
                        if ($this->model->where(['type' => 'shop','shop_id' => $this->shop->id])->count() > 0) {
                            $this->error('店铺首页已经存在');
                        }
                    }
                    $params['page_token'] = Random::alnum(16);
                    $params['page'] = '{"params":{"navigationBarTitleText":"\u6807\u9898","share_title":"\u5206\u4eab\u6807\u9898"},"style":{"navigationBarTextStyle":"#000000","navigationBarBackgroundColor":"#f5f5f5","navigationBarBackgroundImage":"","pageBackgroundColor":"#ffffff","pageBackgroundImage":""}}';
                    $params['item'] = '[]';
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
        return $this->view->fetch();
    }
    
    /**
     * 恢复历史
     */
    public function recover($id = null)
    {
        if ($this->request->isPost()) {
            $row = $this->model
                ->onlyTrashed()
                ->where('id', $id)
                ->find();
            if (!$row) {
                $this->error(__('No Results were found'));
            }
            if ($row['shop_id'] !=$this->shop->id) {
                $this->error(__('You have no permission'));
            }
            $this->success("拉取历史数据成功", "url", $row);
        }
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
     * 真实删除
     */
    public function destroy($ids = "")
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
            foreach ($list as $k => $v) {
                $count += $v->delete(true);
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
        $this->error(__('Parameter %s can not be empty', 'ids'));
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
	
	/**
	 * 选择链接
	 */
	public function link()
	{
	    //设置过滤方法
	    $this->request->filter(['strip_tags']);
	    if ($this->request->isAjax()) {
	        $filter = json_decode($this->request->request('filter'), true);
			$filter = $filter ? $filter['sort'] : false;
	        $list = [];
			// 商品
			if(!$filter || $filter == 'product'){
				$product = model('app\admin\model\wanlshop\Goods')
					->where(['shop_id' => $this->shop->id])
					->select();
				$productData = [];
				foreach($product as $data){
					$productData[] = [
						'title' => $data['title'],
						'image' => $data['image'],
						'route' => '/pages/product/goods?id='.$data['id'],
						'sort' => 'product',
						'sort_text' => __('Sort product')
					];
				}
				$list = array_merge($list,$productData);
			}
			// 分类
			if(!$filter || $filter == 'sort'){
				$sort = model('app\index\model\wanlshop\ShopSort')
					->where(['shop_id' => $this->shop->id])
					->select();
				$sortData = [];
				foreach($sort as $data){
					$sortData[] = [
						'title' => $data['name'],
						'image' => $data['image'],
						'route' => '/pages/shop/productList?shop_id='.$data['shop_id'].'&category_id='.$data['id'].'&category_name='.$data['name'],
						'sort' => 'sort',
						'sort_text' => __('Sort sort')
					];
				}
				$list = array_merge($list,$sortData);
			}
			// 自定义页面
			if(!$filter || $filter == 'page'){
				$page = model('app\index\model\wanlshop\Page')
					->where(['shop_id' => $this->shop->id])
					->select();
				$pageData = [];
				foreach($page as $data){
					$pageData[] = [
						'title' => $data['name'],
						'image' => '',
						'route' => '/pages/page/index?id='.$data['page_token'],
						'sort' => 'page',
						'sort_text' => __('Sort page')
					];
				}
				$list = array_merge($list,$pageData);
			}
	        $result = array("total" => count($list), "rows" => $list);
	        return json($result);
	    }
	    return $this->view->fetch();
	}
}
