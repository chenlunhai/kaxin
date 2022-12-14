<?php

namespace app\api\controller\wanlshop;

use app\common\controller\Api;
use fast\Tree;
use think\Db;

/**
 * WanlShop产品接口
 */
class Product extends Api {

    protected $noNeedLogin = ['lists', 'goods', 'drawer', 'comment', 'stock', 'likes', 'lists_category'];
    protected $noNeedRight = ['*'];
    protected $excludeFields = "";

    public function lists_category() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        // 生成搜索条件
        $where = ["category_id" => $this->request->post("cid")];
        // list($where, $sort, $order) = $this->buildparams('id,title,category.name', false); // 查询标题 和类目字段  ！！！！！！排除已下架//-------------------------------------------
        // 查询数据
        $list = model('app\api\model\wanlshop\Goods')
                ->with(['shop', 'category'])
                ->where($where)
                ->where('goods.status', 'normal')
                ->order("id", 'desc')
                ->paginate();
        foreach ($list as $row) {
            $goodsInfo = \think\Db::name("good_goods")->where(["gid" => $row->id])->find();
            if ($goodsInfo and $goodsInfo["returnmax"] > 0) {
                $hadbuy = \think\Db::name("wanlshop_order")->where(["user_id" => $this->auth->id, "goods_id" => $row["id"], "pay_status" => 1])->count();
                if ($hadbuy < $goodsInfo["join_num"]) {
                    $row["free"] = $goodsInfo["returnmax"];
                } else {
                    $row["free"] = 0;
                }
            } else {
                $row["free"] = 0;
            }
            $row->getRelation('shop')->visible(['city', 'shopname', 'state', 'isself']);
            $row->getRelation('category')->visible(['id', 'pid', 'name']);
            $row->isLive = model('app\api\model\wanlshop\Live')->where(['shop_id' => $row['shop_id'], 'state' => 1])->field('id')->find();
        }
        $this->success('返回成功', $list);
    }

    public function showcategory() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        // 生成搜索条件
        $list = model('app\api\model\wanlshop\Category')->where(["type" => 'goods'])->whereNotIn("id", "104,105,106")->order("weigh desc")->select();

        foreach ($list as $key => $value) {
            $goodsInfo = Db::name("wanlshop_goods")->where(["category_id" => $value["id"], "status" => 'normal'])->where("deletetime is  null")->select();
            foreach ($goodsInfo as $k => $v) {
                $sku = Db::name("wanlshop_goods_sku")->where(["goods_id" => $v["id"]])->order("id desc")->find();
                $goodsInfo[$k]["integral"] = $sku["integral"];
            }

            $list[$key]["childlist"] = $goodsInfo;
        }

        $this->success('返回成功', $list);
    }

    /**
     * 获取商品列表 1.0.3升级 隐藏查询结果 1.0.4升级 错误查询
     *
     * @ApiSummary  (WanlShop 产品接口获取商品列表)
     * @ApiMethod   (GET)
     * 
     */
    public function lists() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        // 生成搜索条件
        list($where, $sort, $order) = $this->buildparams('title,category.name', false); // 查询标题 和类目字段  ！！！！！！排除已下架//-------------------------------------------
        // 查询数据
        $list = model('app\api\model\wanlshop\Goods')
                ->with(['shop', 'category'])
                ->where($where)
                ->where('goods.status', 'normal')
                ->order($sort, $order)
                ->paginate();
   
        foreach ($list as $row) {
            $godss = \think\Db::name("wanlshop_goods")->where(["id" => $row["id"]])->find();
            if ($godss["category_id"] == 104 or $godss["category_id"] == 105) {
                $row["flag"] = false;
            } else {
                $row["flag"] = true;
            }
            $row->getRelation('shop')->visible(['city', 'shopname', 'state', 'isself']);
            $row->getRelation('category')->visible(['id', 'pid', 'name']);
            $row->isLive = model('app\api\model\wanlshop\Live')->where(['shop_id' => $row['shop_id'], 'state' => 1])->field('id')->find();
            
                // $goodsInfo = Db::name("wanlshop_goods")->where(["category_id" => $value["id"], "status" => 'normal'])->where("deletetime is  null")->select();
      
                $sku = Db::name("wanlshop_goods_sku")->where(["goods_id" =>$row["id"]])->order("id desc")->find();
               $row["integral"] = $sku["integral"];
        
            
            
            
        }
        $this->success('返回成功', $list);
    }

    /**
     * 获取品牌列表
     *
     * @ApiSummary  (WanlShop 产品接口获取品牌列表)
     * @ApiMethod   (GET)
     * 
     */
    public function drawer() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        $search = $this->request->request("search"); // 查询商品品牌
        $category_id = $this->request->request("category_id"); // 查询类目品牌
        // 通过商品类目查询
        if ($search) {
            // 生成搜索条件 笨办法，查询第一个产品类目，列出品牌列表和类目属性
            list($where, $sort, $order) = $this->buildparams('title,category.name', false);
            // 查询数据
            $goods = model('app\api\model\wanlshop\Goods')
                    ->with(['category'])
                    ->where($where)
                    ->order($sort, $order)
                    ->find();
            if ($goods) {
                $brand = model('app\api\model\wanlshop\Brand')
                        ->where(['category_id' => $goods['category_id'], 'status' => 'normal'])
                        ->field('name')
                        ->select();
                $attribute = model('app\api\model\wanlshop\Attribute')
                        ->where(['category_id' => $goods['category_id'], 'status' => 'normal'])
                        ->field('name,value')
                        ->select();
                $result = array("brand" => $brand, "attribute" => $attribute);
            } else {
                $result = array("brand" => '', "attribute" => '');
            }
        }
        // 直接查询类目
        if ($category_id) {
            $brand = model('app\api\model\wanlshop\Brand')
                    ->where(['category_id' => $category_id, 'status' => 'normal'])
                    ->field('id,name')
                    ->select();
            $attribute = model('app\api\model\wanlshop\Attribute')
                    ->where(['category_id' => $category_id, 'status' => 'normal'])
                    ->field('name,value')
                    ->select();
            $result = array("brand" => $brand, "attribute" => $attribute);
        }
        $this->success('返回成功', $result);
    }

    /**
     * 获取商品详情
     *
     * @ApiSummary  (WanlShop 产品接口、浏览+1、获取UUID生成访问记录)
     * @ApiMethod   (GET)
     * 
     * @param string $id 商品ID
     */
    public function goods() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        $id = $this->request->request("id");
        // 是否传入商品ID
        $id ? $id : ($this->error(__('非正常访问')));
        // 加载商品模型
        $goodsModel = model('app\api\model\wanlshop\Goods');
        // 查询商品
        $goods = $goodsModel
                ->where(['id' => $id])
                ->field('id,category_id,shop_category_id,brand_id,freight_id,shop_id,title,image,images,flag,content,category_attribute,activity,price,sales,payment,comment,praise,moderate,negative,like,views,status,market_price,sharetitle,shareimage')
                ->find();
        // 浏览+1 & 报错
        if ($goods && $goods['status'] == 'normal') {
            $goods->setInc('views'); // 浏览+1
            $this->addbrowse($goods); // 写入访问日志
        } else {
            $this->error(__('所查找的商品尚未上架'));
        }


        // 查询类目
        $goods->category->visible(['id', 'pid', 'name']);

        // 查询优惠券
        // $goods['coupon'] = controller('api/wanlshop/coupon')->query($goods['id'], $goods['shop_id'], $goods['shop_category_id'], $goods['price'], true);
        $goods['coupon'] = $this->queryCoupon($goods['id'], $goods['shop_id'], $goods['shop_category_id'], $goods['price']);
        // 查询是否关注
        $goods['follow'] = $this->isfollow($id);
        // 查询品牌
        $goods->brand->visible(['name']);
        // 查询SKU
        $goods['sku'] = $goods->sku;
        // 查询SPU
        $goods['spu'] = $goods->spu;
        // 查询直播状态
        $goods['isLive'] = model('app\api\model\wanlshop\Live')->where(['shop_id' => $goods['shop_id'], 'state' => 1])->field('id')->find();
        // 查询评论

        $goods['comment_list'] = $goods->comment_list;
        // 获取店铺详情
        $goods->shop->visible(['shopname', 'service_ids', 'avatar', 'city', 'like', 'score_describe', 'score_service', 'score_logistics']);
        // 查询快递 运费ID 商品重量 邮递城市 商品数量
        $goods['freight'] = $this->freight($goods['freight_id']);
        // 查询促销
        $goods['promotion'] = $id; //--下个版本更新--
        //
        //
        // $godss = \think\Db::name("good_goods")->where(["gid" => $id])->find();
        // if ($godss and $godss["returnmax"] > 0) {
        //     $hadbuy = \think\Db::name("wanlshop_order")->where(["user_id" => $this->auth->id, "goods_id" => $id, "pay_status" => 1])->count();
        //     if ($hadbuy < $godss["join_num"]) {
        //         $goods["free"] = $godss["returnmax"];
        //     } else {
        //         $goods["free"] = 0;
        //     }
        // } else {
        //     $goods["free"] = 0;
        // }
        // 店铺推荐
        $goods['shop_recommend'] = $goodsModel
                ->where(['shop_id' => $goods['shop_id'], 'status' => 'normal']) //还可以使用 , 'flag' => 'recommend'
                ->field('id,title,image,price')
                ->limit(3)
                ->select();
        $this->success('返回成功', $goods);
    }

    /**
     * 实时查询库存
     *
     * @ApiSummary  (WanlShop 保存浏览记录)
     * @ApiMethod   (GET)
     * 
     * @param string $sku_id  SKU
     */
    public function stock($sku_id = '') {
        $this->success('查询成功', model('app\api\model\wanlshop\GoodsSku')->get($sku_id));
    }

    /**
     * 是否关注商品
     *
     * @ApiSummary  (WanlShop 保存浏览记录)
     * @ApiMethod   (GET)
     * 
     * @param string $goods  商品数据
     */
    public function isfollow($goods_id = '') {
        $data = false;
        if ($this->auth->isLogin()) {
            $follow = model('app\api\model\wanlshop\GoodsFollow')->where(['user_id' => $this->auth->id, 'goods_id' => $goods_id])->count();
            $data = $follow == 0 ? false : true; //关注
        }
        return $data;
    }

    /**
     * 保存浏览记录
     *
     * @ApiSummary  (WanlShop 保存浏览记录)
     * @ApiMethod   (GET)
     * 
     * @param string $goods  商品数据
     */
    public function addbrowse($goods = []) {
        //保存浏览记录
        $uuid = $this->request->server('HTTP_UUID');
        if (!isset($uuid)) {
            $charid = strtoupper(md5($this->request->header('user-agent') . $this->request->ip()));
            $uuid = substr($charid, 0, 8) . chr(45) . substr($charid, 8, 4) . chr(45) . substr($charid, 12, 4) . chr(45) . substr($charid, 16, 4) . chr(45) . substr($charid, 20, 12);
        }
        $record = model('app\api\model\wanlshop\Record');
        $where = [
            'uuid' => $uuid,
            'goods_id' => $goods['id']
        ];
        if ($record->where($where)->count() == 0) {
            if ($this->auth->isLogin()) {
                $record->user_id = $this->auth->id;
            }
            $record->uuid = $uuid;
            $record->goods_id = $goods['id'];
            $record->shop_id = $goods['shop_id'];
            $record->category_id = $goods['category']['id'];
            $record->category_pid = $goods['category']['pid'];
            $record->ip = $this->request->ip();
            $record->save();
        } else {
            $record->where($where)->setInc('views'); //访问+1
        }
    }

    /**
     * 关注商品
     *
     * @ApiSummary  (WanlShop 关注或取消商品)
     * @ApiMethod   (POST)
     * 
     * @param string $id 商品ID
     */
    public function follow() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $id = $this->request->post("id");
            // 是否传入商品ID
            $id ? $id : ($this->error(__('非正常访问')));
            // 加载商品模型
            $goodsModel = model('app\api\model\wanlshop\Goods');
            $goodsFollowModel = model('app\api\model\wanlshop\GoodsFollow');
            $data = [
                'user_id' => $this->auth->id,
                'goods_id' => $id
            ];
            if ($goodsFollowModel->where($data)->count() == 0) {
                $goodsFollowModel->save($data);
                $goodsModel->where(['id' => $id])->setInc('like'); //喜欢+1
                $follow = true;
            } else {
                $goodsFollowModel->where($data)->delete();
                $goodsModel->where(['id' => $id])->setDec('like'); //喜欢-1
                $follow = false;
            }
            $this->success('返回成功', $follow);
        }
        $this->error(__('非正常访问'));
    }

    /**
     * 收藏夹列表
     */
    public function collect() {
        $goods = model('app\api\model\wanlshop\GoodsFollow')
                ->where(['user_id' => $this->auth->id])
                ->field('goods_id')
                ->paginate()
                ->each(function ($data, $key) {
            $data['goods'] = $data->goods ? $data->goods->visible(['shop_id', 'title', 'image', 'views', 'price', 'sales', 'payment', 'like']) : [];
            return $data;
        });
        $this->success('返回成功', $goods);
    }

    /**
     * 足迹列表
     */
    public function footprint() {
        $list = model('app\api\model\wanlshop\Record')
                ->where(['user_id' => $this->auth->id])
                ->field('goods_id, createtime')
                ->order('createtime', 'desc')
                ->paginate()
                ->each(function ($data, $key) {
            $data['goods'] = $data->goods ? $data->goods->visible(['image', 'title', 'price', 'payment']) : [];
            return $data;
        });
        $this->success('返回成功', $list);
    }

    /**
     * 查询用户指定店铺浏览记录 
     *
     * @ApiSummary  (查询用户指定店铺浏览记录 1.0.2升级)
     * @ApiMethod   (POST)
     *
     * @param string $shop_id 店铺ID
     */
    public function getBrowsingToShop() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $shop_id = $this->request->post('shop_id');
            $shop_id ? '' : ($this->error(__('Invalid parameters')));
            $list = model('app\api\model\wanlshop\Record')
                    ->where(['shop_id' => $shop_id, 'user_id' => $this->auth->id])
                    ->group('goods_id')
                    ->field('goods_id, createtime')
                    ->select();
            foreach ($list as $row) {
                $row->goods->visible(['id', 'image', 'title', 'price']);
            }
            $this->success(__('发送成功'), $list);
        }
        $this->error(__('非法请求'));
    }

    /**
     * 获取商品评论
     *
     * @ApiSummary  (WanlShop 获取商品下所有评论)
     * @ApiMethod   (POST)
     * 
     * @param string $tag 评论分类
     * @param string $id  商品ID
     * @param string $list_rows  每页数量
     * @param string $page  当前页
     */
    public function comment() {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        $id = $this->request->request("id");
        $tag = $this->request->request('tag');
        // 是否传入商品ID
        $id ? $id : ($this->error(__('非正常访问')));
        // 加载商品模型
        $goodsCommentModel = model('app\api\model\wanlshop\GoodsComment')->order('createtime desc');
        //查询tag 评价:0=好评,1=中评,2=差评
        if ($tag) {
            if ($tag == 'good') {
                $where['state'] = 0;
            } else if ($tag == 'pertinent') {
                $where['state'] = 1;
            } else if ($tag == 'poor') {
                $where['state'] = 2;
            } else if ($tag == 'figure') {
                $where['images'] = ['neq', '']; //有图
            } else {
                $where['tag'] = $tag;
            }
        }
        $where['goods_id'] = $id;
        $comment['comment'] = $goodsCommentModel
                ->with(['user'])
                ->where($where)
                ->paginate();
        // $comment['tag'] = array_count_values($goodsCommentModel->where(['goods_id'=>$id])->limit(100)->column('tag')); //统计热词
        foreach ($comment['comment'] as $row) {
            $row->getRelation('user')->visible(['username', 'nickname', 'avatar']);
        }
        $goods = model('app\api\model\wanlshop\Goods')
                ->where(['id' => $id])
                ->find();
        $comment['statistics'] = [
            'rate' => $goods['comment'] == 0 ? '0' : bcmul(bcdiv($goods['praise'], $goods['comment'], 2), 100, 2),
            'total' => $goods['comment'],
            'good' => $goods['praise'],
            'pertinent' => $goods['moderate'],
            'poor' => $goods['negative'],
            'figure' => $goodsCommentModel->where(['goods_id' => $id])->where('images', 'neq', '')->count()
        ];
        $this->success('返回成功', $comment);
    }

    /**
     * 猜你喜欢
     *
     * @ApiSummary  (WanlShop 猜你喜欢)
     * @ApiMethod   (GET)
     * 
     * @param string $pages 页面ID
     * @param string $category_id 类目ID
     */
    public function likes() {
        $pages = $this->request->request('pages'); //不同页面不同排序,goods只获得与当前产品相同类目,index获得排名靠前的,user随意获取
        $category_id = $this->request->request('cid');
        // 判断来源
        if ($pages == 'index') {
            $sort = 'payment';
        } else if ($pages == 'user') {
            $sort = 'comment';
        } else if ($pages == 'cart') {
            $sort = 'views';
        } else if ($pages == 'goods') {
            $sort = 'weigh';
        } else {
            $sort = 'like';
        }
        $uuid = $this->request->server('HTTP_UUID');
        if (!isset($uuid)) {
            $charid = strtoupper(md5($this->request->header('user-agent') . $this->request->ip()));
            $uuid = substr($charid, 0, 8) . chr(45) . substr($charid, 8, 4) . chr(45) . substr($charid, 12, 4) . chr(45) . substr($charid, 16, 4) . chr(45) . substr($charid, 20, 12);
        }
        // 统计
        $record = model('app\api\model\wanlshop\Record')->where(['uuid' => $uuid]);
        //获取没在活动中的产品
        $where['activity'] = 'false';
        // 获取上架商品 1.0.3升级
        $where['status'] = 'normal';
        //如果没有
        if ($record->count() == 0) {
            if ($category_id) {
                $category_pid = model('app\api\model\wanlshop\Category')->get($category_id);
                $array = model('app\api\model\wanlshop\Category')
                        ->where(['pid' => $category_pid['pid']])
                        ->select();
                $cid = [];
                foreach ($array as $value) {
                    $cid[] = $value['id'];
                }
                $where['category_id'] = ['in', $cid];
            }
            $goods = model('app\api\model\wanlshop\Goods')
                    ->where($where)
                    ->orderRaw('rand()')
                    ->field('id,shop_id,title,image,flag,price,views,sales,comment,praise,like,category_id')
                    ->paginate();
        } else {
            $like_cat = array_count_values($record->column('category_pid')); //喜欢的类目
            $like_goods_cat = array($record->order('views', 'desc')->find()['category_pid']); //喜欢产品的类目
            arsort($like_cat); //排序
            $like_cat_top = array_slice(array_keys($like_cat), 0, 5); //排名前5
            $category_pid = array_intersect($like_cat_top, $like_goods_cat); //是否包含喜欢的产品类目
            // 如果包含输入产品类目,如果不包含输出排名第一的
            if ($category_pid) {
                $category_pid = array_slice($category_pid, 0, 1)[0];
            } else {
                $category_pid = $like_cat_top[0];
            }
            // 查询指定
            if ($category_id) {
                $category_pid = model('app\api\model\wanlshop\Category')->get($category_id)['pid'];
            }
            //查询下级类目
            $array = model('app\api\model\wanlshop\Category')
                    ->where(['pid' => $category_pid])
                    ->select();
            $cid = [];
            foreach ($array as $value) {
                $cid[] = $value['id'];
            }
            $where['category_id'] = ['in', $cid];
            // 查询父ID下所有商品
            $goods = model('app\api\model\wanlshop\Goods')
                    ->where($where)
                    ->orderRaw('rand()')
                    ->field('id,shop_id,title,image,flag,price,views,sales,comment,praise,like,category_id')
                    ->paginate();
        }
        foreach ($goods as $row) {
            // $godss = \think\Db::name("good_goods")->where(["gid" => $row["id"]])->find();
            // if ($godss and $godss["returnmax"] > 0) {
            //     if ($this->auth->id) {
            //         $hadbuy = \think\Db::name("wanlshop_order")->where(["user_id" => $this->auth->id, "goods_id" => $row["id"], "pay_status" => 1])->count();
            //         if ($hadbuy < $godss["join_num"]) {
            //           // $row["free"] = $godss["returnmax"];
            //         } else {
            //             $row["free"] = 0;
            //         }
            //     } else {
            //         $row["free"] = 0;
            //     }
            // } else {
            //     $row["free"] = 0;
            // }
                $godss = \think\Db::name("wanlshop_goods")->where(["id" => $row["id"]])->find();
      
            if ($godss["category_id"] == 104 or $godss["category_id"] == 105) {
                $row["flag"] = false;
            } else {
                $row["flag"] = true;
            }
            $row->shop->visible(['state', 'shopname']);
            $row->isLive = model('app\api\model\wanlshop\Live')->where(['shop_id' => $row['shop_id'], 'state' => 1])->field('id')->find();
        }
        $this->success('返回成功', $goods);
    }

    public function likes2() {
        $pages = $this->request->request('pages'); //不同页面不同排序,goods只获得与当前产品相同类目,index获得排名靠前的,user随意获取
        $category_id = $this->request->request('cid');
        // 判断来源
        if ($pages == 'index') {
            $sort = 'payment';
        } else if ($pages == 'user') {
            $sort = 'comment';
        } else if ($pages == 'cart') {
            $sort = 'views';
        } else if ($pages == 'goods') {
            $sort = 'weigh';
        } else {
            $sort = 'like';
        }
        $uuid = $this->request->server('HTTP_UUID');
        if (!isset($uuid)) {
            $charid = strtoupper(md5($this->request->header('user-agent') . $this->request->ip()));
            $uuid = substr($charid, 0, 8) . chr(45) . substr($charid, 8, 4) . chr(45) . substr($charid, 12, 4) . chr(45) . substr($charid, 16, 4) . chr(45) . substr($charid, 20, 12);
        }
        // 统计
        $record = model('app\api\model\wanlshop\Record')->where(['uuid' => $uuid]);
        //获取没在活动中的产品
        $where['activity'] = 'false';
        // 获取上架商品 1.0.3升级
        $where['status'] = 'normal';
        //如果没有
        if ($record->count() == 0) {
            if ($category_id) {
                $category_pid = model('app\api\model\wanlshop\Category')->get($category_id);
                $array = model('app\api\model\wanlshop\Category')
                        ->where(['pid' => $category_pid['pid']])
                        ->limit(0, 20)
                        ->select();
                $cid = [];
                foreach ($array as $value) {
                    $cid[] = $value['id'];
                }
                $where['category_id'] = ['in', $cid];
            }
            $goods = model('app\api\model\wanlshop\Goods')
                    ->where($where)
                    ->where("category_id not in (104,105)")
                    ->orderRaw('rand()')
                    ->field('id,shop_id,title,image,flag,price,views,sales,comment,praise,like,category_id')
                    ->select();
        } else {
            $like_cat = array_count_values($record->column('category_pid')); //喜欢的类目
            $like_goods_cat = array($record->order('views', 'desc')->find()['category_pid']); //喜欢产品的类目
            arsort($like_cat); //排序
            $like_cat_top = array_slice(array_keys($like_cat), 0, 5); //排名前5
            $category_pid = array_intersect($like_cat_top, $like_goods_cat); //是否包含喜欢的产品类目
            // 如果包含输入产品类目,如果不包含输出排名第一的
            if ($category_pid) {
                $category_pid = array_slice($category_pid, 0, 1)[0];
            } else {
                $category_pid = $like_cat_top[0];
            }
            // 查询指定
            if ($category_id) {
                $category_pid = model('app\api\model\wanlshop\Category')->get($category_id)['pid'];
            }
            //查询下级类目
            $array = model('app\api\model\wanlshop\Category')
                    ->where(['pid' => $category_pid])
                    ->select();
            $cid = [];
            foreach ($array as $value) {
                $cid[] = $value['id'];
            }
            $where['category_id'] = ['in', $cid];
            // 查询父ID下所有商品
            $goods = model('app\api\model\wanlshop\Goods')
                    ->where($where)
                    ->where("category_id not in (104,105)")
                    ->orderRaw('rand()')
                    ->field('id,shop_id,title,image,flag,price,views,sales,comment,praise,like,category_id')
                    ->limit(0, 20)
                    ->select();
        }
        foreach ($goods as $k => $v) {
            $keu = Db::name("wanlshop_goods_sku")->where(["goods_id" => $v["id"]])->order("id desc")->find();
            $goods[$k]["integral"] = $keu["integral"];
            if($v["category_id"]==104 or $v["category_id"]==105){
                 $goods[$k]["flag"] = false;
            }else{
                 $goods[$k]["flag"] = true;
            }

        }

        foreach ($goods as $row) {
      
            $row->shop->visible(['state', 'shopname']);
            $row->isLive = model('app\api\model\wanlshop\Live')->where(['shop_id' => $row['shop_id'], 'state' => 1])->field('id')->find();
        }
        $this->success('返回成功', $goods);
    }

    public function getgoodslist() {
        $tab = input("tab");
        if ($tab == 2) {
            $goods = model('app\api\model\wanlshop\Goods')
                    ->where("category_id not in (104,105)")
                    ->where("status='normal' and deletetime is null")
                    ->orderRaw('rand()')
                    ->order("price desc")
                    ->limit(0, 50)
                    ->field('id,shop_id,title,image,flag,price,views,sales,comment,praise,like,category_id')
                    ->select();
        } else {
            $goods = model('app\api\model\wanlshop\Goods')
                    ->where("category_id not in (104,105)")
                    ->where("status='normal' and deletetime is null")
                    ->orderRaw('rand()')
                    ->order("createtime desc")
                    ->limit(0, 50)
                    ->field('id,shop_id,title,image,flag,price,views,sales,comment,praise,like,category_id')
                    ->select();
        }
        foreach ($goods as $k => $v) {
            if($v["category_id"]==105 or $v["category_id"]==104){
                 $goods[$k]["flag"] = false;
            }else{
                $goods[$k]["flag"] = true;
            }
            $keu = Db::name("wanlshop_goods_sku")->where(["goods_id" => $v["id"]])->order("id desc")->find();
            $goods[$k]["integral"] = $keu["integral"];
        }

        foreach ($goods as $row) {
            $row->shop->visible(['state', 'shopname']);
            $row->isLive = model('app\api\model\wanlshop\Live')->where(['shop_id' => $row['shop_id'], 'state' => 1])->field('id')->find();
        }
        $this->success('返回成功', $goods);
    }

    /**
     * 获取运费模板和子类 内部方法 -----下个版本完善------
     * @param string $id  运费ID
     * @param string $weigh  商品重量
     * @param string $city  邮递城市
     * @param string $number  商品数量
     */
    private function freight($id = null, $weigh = 0, $city = '北京', $number = 1) {
        // 运费模板
        $data = model('app\api\model\wanlshop\ShopFreight')->where('id', $id)->field('id,delivery,isdelivery,name,valuation')->find();
        $data['price'] = 0;
        // 是否包邮:0=自定义运费,1=卖家包邮
        if ($data['isdelivery'] == 0) {
            // 获取地址编码
            $area = model('app\common\model\Area')->where('name', $city)->find();
            $list = model('app\api\model\wanlshop\ShopFreightData')
                    ->where('citys', 'like', '%' . $area['id'] . '%')
                    ->where('freight_id', $id)
                    ->find();
            // 查询是否存在运费模板数据
            if (!$list) {
                $list = model('app\api\model\wanlshop\ShopFreightData')
                        ->where('freight_id', $id)
                        ->find();
            }
            // 计价方式:0=按件数,1=按重量,2=按体积
            if ($data['valuation'] == 0) {
                if ($number <= $list['first']) {
                    $price = $list['first_fee'];
                } else {
                    $price = ceil(($number - $list['first']) / $list['additional']) * $list['additional_fee'] + $list['first_fee'];
                }
            } else {
                $weigh = $weigh * $number; // 订单总重量
                if ($weigh <= $list['first']) { // 如果重量小于等首重，则首重价格
                    $price = $list['first_fee'];
                } else {
                    $price = ceil(($weigh - $list['first']) / $list['additional']) * $list['additional_fee'] + $list['first_fee'];
                }
            }
            $data['price'] = $price;
        }
        return $data;
    }

    /**
     * 查询我的优惠券 内部方法 (跨段存在登录问题，无法解决，暂时复制进来这个方法)
     *
     * @param string $goods_id 商品ID
     * @param string $shop_id 店铺ID
     * @param string $shop_category_id 分类ID
     * @param string $price 价格 
     */
    private function queryCoupon($goods_id = null, $shop_id = null, $shop_category_id = null, $price = null) {
        $user_coupon = [];
        if ($this->auth->isLogin()) {
            foreach (model('app\api\model\wanlshop\CouponReceive')->where([
                'user_id' => $this->auth->id,
                'shop_id' => $shop_id,
                'limit' => ['<=', intval($price)],
                'state' => '1'
            ])->select() as $row) {
                $user_coupon[$row['coupon_id']] = $row;
            }
        }
        // 开始查询 方案一
        $list = [];
        $goods_id = explode(",", $goods_id);
        $shop_category_id = explode(",", $shop_category_id);
        //要追加一个排序 选出一个性价比最高的
        foreach (model('app\api\model\wanlshop\Coupon')->where([
            'shop_id' => $shop_id,
            'limit' => ['<=', intval($price)]
        ])->select() as $row) {
            // 筛选出还未开始的
            if (!($row['pretype'] == 'fixed' && (strtotime($row['startdate']) >= time() || strtotime($row['enddate']) < time()))) {
                //追加字段
                $row['choice'] = false;
                // 检查指定的键名是否存在于数组中
                if (array_key_exists($row['id'], $user_coupon)) {
                    $row['invalid'] = 0; // 强制转换优惠券状态
                    $row['id'] = $user_coupon[$row['id']]['id'];
                    $row['state'] = true;
                } else {
                    $row['state'] = false;
                }
                // 排除失效优惠券
                if ($row['invalid'] == 0) {
                    // 高级查询，比较数组，返回交集如果和原数据数目相同则加入
                    if ($row['rangetype'] == 'all') {
                        $list[] = $row;
                    }
                    if ($row['rangetype'] == 'goods' && count($goods_id) == count(array_intersect($goods_id, explode(",", $row['range'])))) {
                        $list[] = $row;
                    }
                    if ($row['rangetype'] == 'category' && count($shop_category_id) == count(array_intersect($shop_category_id, explode(",", $row['range'])))) {
                        $list[] = $row;
                    }
                }
            }
        }
        return $list;
    }

    /**
     * 生成查询所需要的条件,排序方式
     * @param mixed   $searchfields   快速查询的字段
     * @param boolean $relationSearch 是否关联查询
     * @return array
     */
    protected function buildparams($searchfields = null, $relationSearch = null) {
        $searchfields = is_null($searchfields) ? $this->searchFields : $searchfields;
        $relationSearch = is_null($relationSearch) ? $this->relationSearch : $relationSearch;
        // 获取传参
        $search = $this->request->get("search", '');
        $filter = $this->request->get("filter", '');
        $op = $this->request->get("op", '', 'trim');
        $sort = $this->request->get("sort", !empty($this->model) && $this->model->getPk() ? $this->model->getPk() : 'id');
        $order = $this->request->get("order", "DESC");
        $filter = (array) json_decode($filter, true);
        $op = (array) json_decode($op, true);
        $filter = $filter ? $filter : [];
        $where = [];
        $tableName = '';
        if ($relationSearch) {
            if (!empty($this->model)) {
                $name = \think\Loader::parseName(basename(str_replace('\\', '/', get_class($this->model))));
                $name = $this->model->getTable();
                $tableName = $name . '.';
            }
            $sortArr = explode(',', $sort);
            foreach ($sortArr as $index => & $item) {
                $item = stripos($item, ".") === false ? $tableName . trim($item) : $item;
            }
            unset($item);
            $sort = implode(',', $sortArr);
        }


        // 判断是否需要验证权限
        // if (!$this->auth->match($this->noNeedLogin)) {
        //     $where[] = [$tableName . 'user_id', 'in', $this->auth->id];
        // }

        if ($search) {
            $searcharr = is_array($searchfields) ? $searchfields : explode(',', $searchfields);
            foreach ($searcharr as $k => &$v) {
                $v = stripos($v, ".") === false ? $tableName . $v : $v;
            }
            unset($v);
            $arrSearch = [];
            foreach (explode(" ", $search) as $ko) {
                $arrSearch[] = '%' . $ko . '%';
            }
            $where[] = [implode("|", $searcharr), "LIKE", $arrSearch];
        }
        // 历遍所有
        if (array_key_exists('category_id', $filter)) {
            $filter['category_id'] = implode(',', array_column(Tree::instance()->init(model('app\api\model\wanlshop\Category')->all())->getChildren($filter['category_id'], true), 'id'));
        }
        foreach ($filter as $k => $v) {
            $sym = isset($op[$k]) ? $op[$k] : '=';
            if (stripos($k, ".") === false) {
                $k = $tableName . $k;
            }
            $v = !is_array($v) ? trim($v) : $v;
            $sym = strtoupper(isset($op[$k]) ? $op[$k] : $sym);
            switch ($sym) {
                case '=':
                case '<>':
                    $where[] = [$k, $sym, (string) $v];
                    break;
                case 'LIKE':
                case 'NOT LIKE':
                case 'LIKE %...%':
                case 'NOT LIKE %...%':
                    $where[] = [$k, trim(str_replace('%...%', '', $sym)), "%{$v}%"];
                    break;
                case '>':
                case '>=':
                case '<':
                case '<=':
                    $where[] = [$k, $sym, intval($v)];
                    break;
                case 'FINDIN':
                case 'FINDINSET':
                case 'FIND_IN_SET':
                    $where[] = "FIND_IN_SET('{$v}', " . ($relationSearch ? $k : '`' . str_replace('.', '`.`', $k) . '`') . ")";
                    break;
                case 'IN':
                case 'IN(...)':
                case 'NOT IN':
                case 'NOT IN(...)':
                    $where[] = [$k, str_replace('(...)', '', $sym), is_array($v) ? $v : explode(',', $v)];
                    break;
                case 'BETWEEN':
                case 'NOT BETWEEN':
                    $arr = array_slice(explode(',', $v), 0, 2);
                    if (stripos($v, ',') === false || !array_filter($arr)) {
                        continue 2;
                    }
                    //当出现一边为空时改变操作符
                    if ($arr[0] === '') {
                        $sym = $sym == 'BETWEEN' ? '<=' : '>';
                        $arr = $arr[1];
                    } elseif ($arr[1] === '') {
                        $sym = $sym == 'BETWEEN' ? '>=' : '<';
                        $arr = $arr[0];
                    }
                    $where[] = [$k, $sym, $arr];
                    break;
                case 'RANGE':
                case 'NOT RANGE':
                    $v = str_replace(' - ', ',', $v);
                    $arr = array_slice(explode(',', $v), 0, 2);
                    if (stripos($v, ',') === false || !array_filter($arr)) {
                        continue 2;
                    }
                    //当出现一边为空时改变操作符
                    if ($arr[0] === '') {
                        $sym = $sym == 'RANGE' ? '<=' : '>';
                        $arr = $arr[1];
                    } elseif ($arr[1] === '') {
                        $sym = $sym == 'RANGE' ? '>=' : '<';
                        $arr = $arr[0];
                    }
                    $where[] = [$k, str_replace('RANGE', 'BETWEEN', $sym) . ' time', $arr];
                    break;
                case 'LIKE':
                case 'LIKE %...%':
                    $where[] = [$k, 'LIKE', "%{$v}%"];
                    break;
                case 'NULL':
                case 'IS NULL':
                case 'NOT NULL':
                case 'IS NOT NULL':
                    $where[] = [$k, strtolower(str_replace('IS ', '', $sym))];
                    break;
                default:
                    break;
            }
        }
        $where = function ($query) use ($where) {
            foreach ($where as $k => $v) {
                if (is_array($v)) {
                    call_user_func_array([$query, 'where'], $v);
                } else {
                    $query->where($v);
                }
            }
        };
        return [$where, $sort, $order];
    }

}
