<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\api\controller\wanlshop;

use app\common\controller\Api;
use \think\Db;
use app\common\library\Auth;
use think\Request;
use fast\Http;
use fast\Random;
use WanlPay\Yansongda\Pay;
use WanlPay\Yansongda\Log;
use addons\wanlshop\library\WanlPay\WanlPay;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Csv;

/**
 * Description of Dollor
 *
 * @author Administrator
 */
class Kaxin extends Api {

    protected $noNeedLogin = ["getbrand", "gettaxdetal","saveInfo"];
    protected $noNeedRight = ['*'];
    private $type;
    private $method;
    private $code;

//put your code here
    public function getbrand() {
        if ($this->request->isPost()) {
            $list = Db::name("wanlshop_brand")->where(["status" => 'normal'])->select();
            $this->success(__('ok'), ["data" => $list]);
        } else {
            $this->error(__('非法请求'));
        }
    }

    public function gettaxdetal() {

        if ($this->request->isPost()) {
            $id = $this->request->post('id');
            if ($id) {
                $list = Db::name("kaxin_brandtax")->where(["id" => $id])->find();
            } else {
                $list = Db::name("kaxin_brandtax")->where(["id" => 1])->find();
            }
            $data = Db::name("kaxin_config_tax")->where("1=1")->select();
                 
            if ($list) {
                $attt = explode(",", $list["tax"]);
                  $money=explode(",", $list["money"]);
            } else {
                $attt = explode(",", '0,0,0,0,0,0,0,0,0');
                  $money=explode(",", '0,0,0,0,0,0,0,0,0');
            } 
            $a = 0;
            $b=0;
            foreach ($data as $key => $value) {
                $data[$key]["tax"] = $attt[$a++];
                 $data[$key]["money"]=$money[$b++];
            }
            //$data["tax"] = $list;
            $this->success(__('ok'), ["data" => $data]);
        } else {
            $this->error(__('非法请求'));
        }
    }

    public function saveInfo() {
        if ($this->request->isPost()) {
            $id = $this->request->post('id');
            $data = $this->request->post('data');
            $taxdata = $this->request->post('taxData');
                $money = $this->request->post('money');
            $data_array = explode("|", $data);
            $a = 1;
            foreach ($data_array as $key => $value) {
                $L_data = explode(",", $value);
                Db::name("kaxin_config_tax")->where(["id" => $a++])->update(["money" => $L_data[1], "personcount" => $L_data[2], "soncount" => $L_data[3]]);
            }
            $brand = Db::name("kaxin_brandtax")->where(["id" => $id])->find();
            if ($brand) {
                Db::name("kaxin_brandtax")->where(["bid" => $id])->update(["tax" => $taxdata,"money"=>$money]);
            } else {
                Db::name("kaxin_brandtax")->insert(["bid" => $id, "tax" => $taxdata,"money"=>$money]);
            }

            $this->success(__('ok'));
        } else {
            $this->error(__('非法请求'));
        }
    }

    public function getpossn() {
        if ($this->request->isPost()) {
            $id = $this->request->post('sn');
            $list = Db::name("kaxin_pos")->where(["flag" => 0])->whereLike("possn", "%" . $id . "%")->select();
            $this->success(__('ok'), $list);
        } else {
            $this->error(__('非法请求'));
        }
    }

    public function turn2friends() {
        if ($this->request->isPost()) {
            $id = $this->request->post('snlist');
            $mobile = $this->request->post('mobile');
            $uInfo = Db::name("user")->where(["mobile" => $mobile])->find();
            $sinfo = Db::name("user")->where(["id" => $this->auth->id])->find();
            $flag = false;
            $uid = $this->auth->id;
            if (!$uInfo) {
                return json(["code" => 1, "data" => ["msg" => '未找到该用户!']]);
            }
            if (strstr($uInfo["pids"], strval($uid)) || strstr($sinfo["pids"], strval($uInfo["id"]))) {
                $flag = true;
            }
            if ($uInfo and $flag) {
                $slist = explode(',', $id);
                $insert = [];
                foreach ($slist as $k => $v) {
                    $old_sn = Db::name("kaxin_user_sn")->where(["uid" => $this->auth->id, "snid" => $v, "status" => 0])->find();
                    if ($old_sn) {
                        Db::name("kaxin_user_sn")->where(["uid" => $this->auth->id, "snid" => $v, "status" => 0])->update(["updatetime" => time(), "flag" => 1, "belongsto" => 1]);
                        Db::name("kaxin_user_sn")->insert(["uid" => $uInfo["id"], "snid" => $v, "flag" => 2]);
                        $insert[] = [
                            "uid" => $this->auth->id,
                            "event" => "转出机具-->" . $mobile,
                            "ftype" => "out",
                            "sn" => $v
                        ];
                        $insert[] = [
                            "uid" => $this->auth->id,
                            "event" => "转入机具<--" . $uInfo["mobile"],
                            "ftype" => "in",
                            "sn" => $v
                        ];
                    }
                }
                Db::name("kaxin_card_turn_log")->insertAll($insert);
                $this->success(__('ok'), ['msg' => '转出成功！']);
            } else {

                return json(["code" => -1, "msg" => '未找到该用户/不在同一网体！']);
                //$this->error(__('未找到该用户/不在同一网体！'));
            }
        } else {
            $this->error(__('非法访问！'));
        }
    }

    public function turn2friendsMuti() {
        if ($this->request->isPost()) {
            $startid = $this->request->post('starid');
            $endid = $this->request->post('endid');
            $mobile = $this->request->post('mobile');
            $uInfo = Db::name("user")->where(["mobile" => $mobile])->find();
            $sinfo = Db::name("user")->where(["id" => $this->auth->id])->find();
            $flag = false;
            if (!$uInfo) {
                return json(["code" => 1, "data" => ["msg" => '未找到该用户!']]);
            }
            $uid = $this->auth->id;
            if (strstr($uInfo["pids"], strval($uid)) || strstr($sinfo["pids"], strval($uInfo["id"]))) {
                $flag = true;
            }
            $insert = [];
            $a = 0;
            if ($uInfo) {
                $old_sn = Db::name("kaxin_user_sn")->where(["uid" => $this->auth->id, "status" => 0])->where("id>=" . $startid . " and id<=" . $endid)->select();
                foreach ($old_sn as $k => $v) {
                    Db::name("kaxin_user_sn")->where(["uid" => $this->auth->id, "snid" => $v["snid"], "status" => 0])->update(["updatetime" => time(), "flag" => 1]);
                    Db::name("kaxin_user_sn")->insert(["uid" => $uInfo["id"], "snid" => $v["snid"], "flag" => 2]);
                    $insert[] = [
                        "uid" => $this->auth->id,
                        "event" => "转出机具-->" . $mobile,
                        "ftype" => "out",
                        "sn" => $v["snid"]
                    ];
                    $insert[] = [
                        "uid" => $this->auth->id,
                        "event" => "转入机具<--" . $uInfo["mobile"],
                        "ftype" => "in",
                        "sn" => $v["snid"]
                    ];
                    $a++;

//                    else {
//                        $this->error(__('未找到该机具！'));
//                    }
                }
                if ($a > 0) {
                    Db::name("kaxin_card_turn_log")->insertAll($insert);
                    $this->success(__('ok'), ['msg' => '成功转出#' . $a . "具"]);
                }
            } else {
                $this->error(__('未找到该用户'));
            }
        } else {
            $this->error(__('非法请求'));
        }
    }

    public function getMachineList() {
        if ($this->request->isPost()) {
            $type = $this->request->Post("type");
            if ($type == 0) {
                $uid = $this->auth->id;
                $list = Db::name("wanlshop_brand")->where(["status" => 'normal'])->limit(0, 300)->select();
                $today = strtotime(date("Y-m-d", time()));
                $mounth = strtotime(date("Y-m", time()));
                $t = $m = $ta = $ma = $mounth_trade = $active_total = $machine_total = 0;
                foreach ($list as $key => $value) {
                    $total = Db::query("select a.*,c.id as bid,c.name as brandname  from xsh_kaxin_user_sn a ,xsh_kaxin_pos b,xsh_wanlshop_brand c  where a.snid=b.possn and a.uid='$uid' and a.belongsto=0  and b.bid=c.id and b.bid=" . $value["id"]);
                    if ($total) {
                        $list[$key]["total"] = count($total);
                        $a = 0;
                        foreach ($total as $k => $va) {
                            $table_num = substr($va["snid"], strlen($va["snid"]) - 1, strlen($va["snid"]));
                            $total_trade = Db::name("kaixin_merchant_trade_notice_" . $table_num)->where(["posSn" => $va["snid"]])->where("addtime", ">", date("Y-m", time()))->sum("amount");
                            $machine_total += 1;
                            if ($va["status"] > 0) {
                                $a += 1;
                                $active_total += 1;
                            }
                            if ($va["updatetime"] > $today) {
                                $t += 1;
                            }
                            if ($va["updatetime"] > $today and $va["status"] > 0) {
                                $ta += 1;
                            }
                            if ($va["updatetime"] > $mounth) {
                                $m += 1;
                            }
                            if ($va["updatetime"] > $mounth and $va["status"] > 0) {
                                $ma += 1;
                            }
                            $mounth_trade += $total_trade;
                        }
                        $list[$key]["active"] = $a;
                    } else {
                        $list[$key]["total"] = 0;
                        $list[$key]["active"] = 0;
                    }
                }
                $list["extra"]["today"] = $t;
                $list["extra"]["mounth"] = $m;
                $list["extra"]["today_active"] = $ta;
                $list["extra"]["mounth_active"] = $ma;
                $list["extra"]["mounth_trade"] = $mounth_trade;
                $list["extra"]["total_active"] = $active_total;
                $list["extra"]["total"] = $machine_total;
                $this->success(__('ok'), $list);
                //return json(["code" => 1, "msg" => "ok", "data" => $list, "extra" => $extra]);
                //
            } else {
                
            }
        } else {
            $this->error(__('非法请求'));
        }
    }

    public function getMachineByBrand() {
        if ($this->request->isPost()) {
            $type = $this->request->Post("type");
            $search = $this->request->post("search");
            if ($type != 0) {
                $uid = $this->auth->id;
                $t = $m = 0;
                if ($search) {
                    $total = Db::query("select a.*,c.id as bid,c.name as brandname  from xsh_kaxin_user_sn a ,xsh_kaxin_pos b,xsh_wanlshop_brand c  where a.snid=b.possn and a.belongsto=0 and a.uid='$uid' and b.bid=c.id and b.bid=" . $type . "and a.posSn like '%$search%'");
                } else {
                    $total = Db::query("select a.*,c.id as bid,c.name as brandname  from xsh_kaxin_user_sn a ,xsh_kaxin_pos b,xsh_wanlshop_brand c  where a.snid=b.possn and a.belongsto=0 and a.uid='$uid' and b.bid=c.id and b.bid=" . $type);
                }
                if ($total) {
                    $a = 0;
                    foreach ($total as $k => $va) {
                        $table_num = substr($va["snid"], strlen($va["snid"]) - 1, strlen($va["snid"]));
                        $total_trade = Db::name("kaixin_merchant_trade_notice_" . $table_num)->where(["posSn" => $va["snid"]])->sum("amount");
                        $total[$k]["total_trade"] = $total_trade;
                        $CustomerInfo = Db::name("kaixin_merchant_bingding_notice")->where(["posSn" => $va["snid"]])->find();
                        if ($CustomerInfo) {
                            $total[$k]["merchant_name"] = "13000000000(卡新科技)"; //$CustomerInfo["phoneNophoneNo"] . "(" . $CustomerInfo["customerName"] . ")";
                        } else {
                            $days = \app\api\controller\Tools::getConfig1("activedays");
                            $nowdays = intval($days - (time() - strtotime($va["addtime"])) / 86400);
                            $total[$k]["active_days"] = $nowdays;
                            $total[$k]["merchant_name"] = "未激活";
                        }
                        if ($va["status"] > 0) {
                            $total[$k]["active_time"] = $va["updatetime"];
                        } else {
                            $total[$k]["active_time"] = "0000-00-00 00:00:00";
                        }
                    }
                }
            } else {
                
            }

            $list["today"] = $t;
            $list["mounth"] = $m;
            $this->success(__('ok'), $total);
        } else {
            $this->error(__('非法请求'));
        }
    }

    public function getMachineLog() {
        if ($this->request->isPost()) {
            $id = $this->request->post('sn') == "" ? 0 : $this->request->post('sn');
            $list = Db::name("kaxin_card_turn_log")->where(["uid" => $this->auth->id])->order("id desc")->limit(0, 300)->select();
            $this->success(__('ok'), $list);
        } else {
            $this->error(__('非法请求'));
        }
    }

    public function importsn() {
        if ($this->request->isPost()) {
            $file = $this->request->request('file');
            if (!$file) {
                $this->error(__('Parameter %s can not be empty', 'file'));
            }
            $filePath = ROOT_PATH . DS . 'public' . DS . $file;
            if (!is_file($filePath)) {
                $this->error(__('No results were found'));
            }
            //实例化reader
            $ext = pathinfo($filePath, PATHINFO_EXTENSION);
            if (!in_array($ext, ['csv', 'xls', 'xlsx'])) {
                $this->error(__('Unknown data format'));
            }
            if ($ext === 'csv') {
                $file = fopen($filePath, 'r');
                $filePath = tempnam(sys_get_temp_dir(), 'import_csv');
                $fp = fopen($filePath, "w");
                $n = 0;
                while ($line = fgets($file)) {
                    $line = rtrim($line, "\n\r\0");
                    $encoding = mb_detect_encoding($line, ['utf-8', 'gbk', 'latin1', 'big5']);
                    if ($encoding != 'utf-8') {
                        $line = mb_convert_encoding($line, 'utf-8', $encoding);
                    }
                    if ($n == 0 || preg_match('/^".*"$/', $line)) {
                        fwrite($fp, $line . "\n");
                    } else {
                        fwrite($fp, '"' . str_replace(['"', ','], ['""', '","'], $line) . "\"\n");
                    }
                    $n++;
                }
                fclose($file) || fclose($fp);

                $reader = new Csv();
            } elseif ($ext === 'xls') {
                $reader = new Xls();
            } else {
                $reader = new Xlsx();
            }

            try {
                if (!$PHPExcel = $reader->load($filePath)) {
                    $this->error(__('Unknown data format'));
                }
                $currentSheet = $PHPExcel->getSheet(0);  //读取文件中的第一个工作表
                $allColumn = $currentSheet->getHighestDataColumn(); //取得最大的列号
                $allRow = $currentSheet->getHighestRow(); //取得一共有多少行
                $maxColumnNumber = Coordinate::columnIndexFromString($allColumn);
                $fields = [];
                for ($currentRow = 1; $currentRow <= 1; $currentRow++) {
                    for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                        $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                        $fields[] = $val;
                    }
                }
                for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                    $values = [];
                    for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                        $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                        $values[] = is_null($val) ? '' : $val;
                    }
                    $row = [];
                    $temp = array_combine($fields, $values);
                    foreach ($temp as $k => $v) {
                        if (isset($fieldArr[$k]) && $k !== '') {
                            $row["possn"] = $v;
                        }
                    }
                    if ($row) {
                        $insert[] = $row;
                    }
                }
            } catch (Exception $exception) {
                $this->error($exception->getMessage());
            }
        }
        $this->error(__('非法请求'));
    }

    public function getdmlist() {
        if ($this->request->isPost()) {
            $order = $this->request->post('order');
            $list = Db::name("user_dm_log ")->where(["user_id" => $this->auth->id])->order("id desc")->limit(300)->select();
            foreach ($list as $key => $value) {
                if ($value["type"] == 'Transfer') {
                    $list[$key]["type"] = '划转';
                } elseif ($value["type"] == 'sale') {
                    $list[$key]["type"] = '出售';
                } elseif ($value["type"] == 'buy') {
                    $list[$key]["type"] = '购买';
                } else {
                    $list[$key]["type"] = '奖励';
                }
            }
            $this->success(__('发送成功'), $list);
        }
        $this->error(__('非法请求'));
    }

    public function buy() {
        if ($this->request->isPost()) {
            $dm_num = $this->request->post('dm_num');
            $saleid = $this->request->post('saleid');
            $saleInfo = Db::name("dm_sale")->where(["id" => $saleid])->find();

            if ($saleInfo) {
                if ($saleInfo["salenum"] >= $dm_num) {
                    $hadbuy = Db::name("dm_buy")->where(["buyer" => $this->auth->id])->order("id desc")->find();
                    if ($hadbuy) {
                        if (time() - $hadbuy["buytime"] <= 1800) {
                            $this->error(__('30分钟内已存在订单'));
                        }
                    }
                    $balance_no = date('YmdHis') . rand(10000000, 99999999);
                    $total = $dm_num * \app\api\controller\Tools::getConfig1("Red_Price");
                    $result = Db::name("dm_buy")->insert(["buyer" => $this->auth->id, "saleid" => $saleid, "buynum" => $dm_num, "buyteime" => time(), "buy_no" => $balance_no, 'totalmoney' => $total]);
                    $this->success(__('订单提交成功'), ["buyid" => $result, "order_sn" => $balance_no]);
                } else {
                    $this->error(__("出售交易已刷新"));
                }
            } else {
                $this->error(__('未查询到该出售订单'));
            }
        }
        $this->error(__('非法请求'));
    }

    public function buypay() {
//设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isPost()) {
            $this->type = $this->request->post('type');
            $this->code = $this->request->post('code');
            $this->method = $this->request->post('method');
            $buyid = $this->request->post('buyid');
            $user_id = $this->auth->id;
            $this->type ? $this->type : ($this->error(__('未选择支付类型')));
            $buyInfo = Db::name("dm_buy")->where(["id" => $buyid])->order("id desc")->find();
            // $dm_price = \app\api\controller\Tools::getConfig1("Red_Price");
            $price = $buyInfo["buynum"] * 1.088; // 调用支付
            if ($this->type == 'balance') {
                $user = model('app\common\model\User')->get($this->auth->id);
                if (!$user || $user['money'] < $price) {
                    return ['code' => 500, 'msg' => '余额不足本次支付'];
                }
                $result = false;
                $enoughDM = Db::name("dm_sale")->where(["id" => $buyInfo["saleid"]])->find();
                if ($enoughDM["salenum"] >= $buyInfo["buynum"]) {
                    Db::startTrans();
                    try {
                        // 修改用户金额

                        $saleInfo = Db::name("dm_sale")->where(["id" => $buyInfo["saleid"]])->find();
                        $result = self::money(-$price, $buyInfo["buyer"], "余额支付DM订单#0", 'pay', "buy_" . time() . "_" . $buyid);
                        self::money($price, $saleInfo["saller"], "DM卖出收益", 'pay', "buy_" . time() . "_" . $buyid);
                        Db::name("dm_sale")->where(["id" => $buyInfo["saleid"]])->setDec("salenum", $buyInfo["buynum"]);

                        Db::commit();
                    } catch (Exception $e) {
                        Db::rollback();
                        $this->error(__('未查询到该出售订单'), ['code' => 10002, 'msg' => $e->getMessage()]);
                    }
                } else {
                    $this->error(__('商户可用DM不足'), ['code' => 10002]);
                }
                // 返回结果
                if ($result !== false) {

                    $this->success(__('支付成功'), ['code' => 200, 'msg' => '成功', 'data' => []]);
                } else {
                    return ['code' => 10003, 'msg' => '支付异常'];
                }
            } else if ($this->type == 'alipay') {
                $data = [
                    'out_trade_no' => $pay_no,
                    'total_amount' => $price,
                    'subject' => $title
                ];
                try {
                    $alipay = Pay::alipay($this->getConfig($this->type))->{$this->method}($data);
                    if ($this->method == 'app' || $this->method == 'wap') {
                        return ['code' => 200, 'msg' => '成功', 'data' => $alipay->getContent()];
                    } else {
                        return ['code' => 200, 'msg' => '成功', 'data' => $alipay];
                    }
                } catch (\Exception $e) {
                    return ['code' => 10006, 'msg' => $this->type . '：' . $e->getMessage()];
                }

// 微信支付
            } else if ($this->type == 'wechat') {

                $wanlPay = new WanlPay($this->type, $this->method, $this->code);
                $data = $wanlPay->pay($buyid, "dm");
                if ($data['code'] == 200) {
                    $this->success('ok', $data['data']);
                } else {
                    $this->error($data['msg']);
                }
//                $pay_no = $buyInfo["buy_no"];
//                $data = [
//                    'out_trade_no' => $pay_no, // 订单号
//                    'body' => '商城订单_' . $pay_no, // 标题
//                    'total_fee' => intval($price * 100) //付款金额 单位分
//                ];
//                if ($this->method == 'miniapp' || $this->method == 'mp') {
//// 获取微信openid，前期版本仅可安全获取，后续版本优化登录逻辑
//                    $config = get_addon_config('wanlshop');
//                    $params = [
//                        'appid' => $config['mp_weixin']['appid'],
//                        'secret' => $config['mp_weixin']['appsecret'],
//                        'js_code' => $this->code,
//                        'grant_type' => 'authorization_code'
//                    ];
//                    $time = time();
//                    $result = Http::sendRequest("https://api.weixin.qq.com/sns/jscode2session", $params, 'GET');
//                    if ($result['ret']) {
//                        $json = (array) json_decode($result['msg'], true);
//                        $third = model('app\api\model\wanlshop\Third')->get(['platform' => 'weixin_open', 'openid' => $json['openid']]);
//                        if (!$third) {
//                            $third = model('app\api\model\wanlshop\Third');
//// array_key_exists("unionid",$json)
//                            if (isset($json['unionid'])) {
//                                $third->unionid = $json['unionid'];
//                                $third->openid = $json['openid'];
//                            } else {
//                                $third->openid = $json['openid'];
//                            }
//                            $third->access_token = $json['session_key'];
//                            $third->expires_in = 7776000;
//                            $third->logintime = $time;
//                            $third->expiretime = $time + 7776000;
//                            $third->user_id = $user_id;
//                            $third->save();
//                        }
//                        $data['openid'] = $json['openid'];
//                    } else {
//                        $this->error(__('获取微信openid失败'), ['code' => 10005, 'msg' => '获取微信openid失败，无法支付']);
//                        //  return ['code' => 10005, 'msg' => '获取微信openid失败，无法支付'];]
//                    }
//                }
//                try {
//                    //dump($data);
//                    $wechat = Pay::wechat($this->getConfig($this->type))->{$this->method}($data);
//                    if ($this->method == 'app') {
//                        $this->success(__('提交成功'), ['code' => 200, 'msg' => '成功', 'data' => $wechat->getContent()]);
//                        // return ['code' => 200, 'msg' => '成功', 'data' => $wechat->getContent()];
//                    } else if ($this->method == 'wap') {
//                        $this->success(__('提交成功'), ['code' => 200, 'msg' => '成功', 'data' => $wechat->getTargetUrl()]);
//                        // return ['code' => 200, 'msg' => '成功', 'data' => $wechat->getTargetUrl()];
//                    } else {
//                        $this->success(__('提交成功'), ['code' => 200, 'msg' => '成功', 'data' => $wechat]);
//                        //return ['code' => 200, 'msg' => '成功', 'data' => $wechat];
//                    }
//                } catch (\Exception $e) {
//                    $this->error(__('获取微信openid失败'), ['code' => 10006, 'msg' => $this->type . '：' . $e->getMessage()]);
//                    // return ['code' => 10006, 'msg' => $this->type . '：' . $e->getMessage()];
//                }
// 百度支付
            } else {
                try {
                    
                } catch (\Exception $e) {
                    return ['code' => 10006, 'msg' => $this->type . '：' . $e->getMessage()];
                }
            }
            if ($data['code'] == 200) {
                $this->success('ok', "ok");
            } else {
                $this->error("提交失败");
            }
        }
        // $this->error(__('非正常请求'));
    }

    public static function money($money, $user_id, $memo, $type = '', $ids = '', $oid = 0) {
        $user = model('app\common\model\User')->get($user_id);
        if ($user && $money != 0) {
            $before = $user->money;
            $after = function_exists('bcadd') ? bcadd($user->money, $money, 2) : $user->money + $money;
//更新会员信息
            $user->save(['money' => $after]);
//写入日志
            $row = model('app\common\model\MoneyLog')->create([
                'user_id' => $user_id,
                'money' => $money, // 操作金额
                'before' => $before, // 原金额
                'after' => $after, // 增加后金额
                'memo' => $memo, // 备注
                'type' => $type, // 类型
                'service_ids' => $ids, // 业务ID
                'oid' => $oid
            ]);
            return $row;
        } else {
            return ['code' => 500, 'msg' => '变更金额失败'];
        }
    }

    public function getConfig() {
        $config = get_addon_config('wanlshop');

        $pay_config = [];
        if ($this->type == 'alipay') {
            $pay_config = [
                'app_id' => $config['sdk_alipay']['app_id'],
                'notify_url' => $config['ini']['appurl'] . $config['sdk_alipay']['notify_url'],
                'return_url' => $config['ini']['appurl'] . $config['sdk_alipay']['return_url'],
                'ali_public_key' => $config['sdk_alipay']['ali_public_key'],
                'private_key' => $config['sdk_alipay']['private_key'],
                'log' => [// optional
                    'file' => LOG_PATH . 'wanlpay' . DS . $this->type . '-' . date("Y-m-d") . '.log',
                    'level' => 'info', // 建议生产环境等级调整为 info，开发环境为 debug
                    'type' => 'single', // optional, 可选 daily.
                    'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
                ],
                'http' => [// optional
                    'timeout' => 5.0,
                    'connect_timeout' => 5.0
                ],
                    // 'mode' => 'dev', // optional,设置此参数，将进入沙箱模式
            ];
        } else if ($this->type == 'wechat') {
            $pay_config = [
                'appid' => $config['sdk_qq']['wx_appid'], // APP APPID
                'app_id' => $config['sdk_qq']['gz_appid'], // 公众号 APPID
                'miniapp_id' => $config['mp_weixin']['appid'], // 小程序 APPID
                'mch_id' => $config['sdk_qq']['mch_id'],
                'key' => $config['sdk_qq']['key'],
                'notify_url' => $config['ini']['appurl'] . $config['sdk_qq']['notify_url'],
                'log' => [// optional
                    'file' => LOG_PATH . 'wanlpay' . DS . $this->type . '-' . date("Y-m-d") . '.log',
                    'level' => 'info', // 建议生产环境等级调整为 info，开发环境为 debug
                    'type' => 'single', // optional, 可选 daily.
                    'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
                ],
                'http' => [// optional
                    'timeout' => 5.0,
                    'connect_timeout' => 5.0,
                // 更多配置项请参考 [Guzzle](https://guzzle-cn.readthedocs.io/zh_CN/latest/request-options.html)
                ],
                    // 'mode' => 'dev',
            ];
            if ($config['sdk_qq']['pay_cert'] == 1) {
                $pay_config['cert_client'] = ADDON_PATH . 'wanlshop' . DS . 'certs' . DS . $this->type . DS . 'apiclient_cert.pem'; // optional, 退款，红包等情况时需要用到
                $pay_config['cert_key'] = ADDON_PATH . 'wanlshop' . DS . 'certs' . DS . $this->type . DS . 'apiclient_key.pem'; // optional, 退款，红包等情况时需要用到
            }
        }
        return $pay_config;
    }

}
