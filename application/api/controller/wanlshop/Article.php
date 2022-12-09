<?php

namespace app\api\controller\wanlshop;

use app\common\controller\Api;

/**
 * WanlShop文章接口
 */
class Article extends Api {

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 获取指定文章列表
     *
     * @ApiSummary  (WanlShop 获取文章列表)
     * @ApiMethod   (POST)
     * 
     * @param string $type 文章类型
     * @param string $list_rows 每页数量
     */
    public function getList() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $type = $this->request->post('type');
            $where['status'] = 'normal';
            $config = get_addon_config('wanlshop');
            if ($type == 'help') {
                $where['category_id'] = $config['config']['help_category'];
            }
            if ($type == 'new') {
                $where['category_id'] = $config['config']['new_category'];
            }
            if ($type == 'sys') {
                $where['category_id'] = $config['config']['sys_category'];
            }
            $data = model('app\api\model\wanlshop\Article')
                    ->where($where)
                    ->field('id,title,description,image,images,flag,views,createtime')
                    ->order('createtime desc')
                    ->paginate();

            $this->success('返回成功', $data);
        }
        $this->error(__('非法请求'));
    }
   
    

    public function getList2() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $type = $this->request->post('type');
            $where['status'] = 'normal';
            $config = get_addon_config('wanlshop');
            if ($type == 'stu') {
                $where['category_id'] = 110;
            } else {
                $where['category_id'] = 111;
            }

            $data = model('app\api\model\wanlshop\Article')
                    ->where($where)
                    ->field('id,title,description,image,images,flag,views,createtime')
                    ->order('createtime desc')
                    ->paginate();

            $this->success('返回成功', $data);
        }
        $this->error(__('非法请求'));
    }

    /**
     * 获取内容详情
     *
     * @ApiSummary  (WanlShop 获取内容详情)
     * @ApiMethod   (POST)
     * 
     * @param string $id 文章ID
     */
    public function details() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        $id = $this->request->get('id');
        $id ? $id : ($this->error(__('Invalid parameters')));
        $row = model('app\api\model\wanlshop\Article')
                ->where(['id' => $id])
                ->find();
        // 1.0.5升级
        if (!$row) {
            $this->error(__('没有找到任何内容'));
        }
        // 点击 +1
        $row->setInc('views');
        $this->success('返回成功', $row);
    }

    /**
     * 获取广告详情
     *
     * @ApiSummary  (WanlShop 获取内容详情)
     * @ApiMethod   (POST)
     * 
     * @param string $id 文章ID
     */
    public function adDetails($id = null) {
        $row = model('app\api\model\wanlshop\Advert')->get($id);
        // 1.0.5升级
        if (!$row) {
            $this->error(__('没有找到任何内容'));
        }
        // 点击 +1
        $row->setInc('views');
        $this->success('返回成功', $row);
    }

    public function getswilist() {
        
        $data=["https://miaoxiang.oss-cn-beijing.aliyuncs.com/banner/%E5%BE%AE%E4%BF%A1%E5%9B%BE%E7%89%87_20220812154746.jpg","https://miaoxiang.oss-cn-beijing.aliyuncs.com/banner/%E5%BE%AE%E4%BF%A1%E5%9B%BE%E7%89%87_20220812154751.jpg","https://miaoxiang.oss-cn-beijing.aliyuncs.com/banner/%E5%BE%AE%E4%BF%A1%E5%9B%BE%E7%89%87_20220812154757.jpg"];
            $this->success('返回成功', $data);
    }
    
}
