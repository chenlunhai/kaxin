<?php

namespace app\api\controller;

use app\common\controller\Api;

/**
 * 首页接口
 */
class Index extends Api {

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 首页
     *
     */
    public function index() {

        $this->success('请求成功');
        ///差窜错误大幅度达到发发发
    }

    public function vip() {
        $list = \think\Db::name("level_config")->select();
        $this->success('获取成功', $list);
    }

    public function addetail() {
        $post = $this->request->post();
        $id = $post["id"];
        $list = \think\Db::name("wanlshop_advert")->where(["id" =>$id])->select();
        $this->success('获取成功', $list);
    }

    public function adlist() {
        $list = \think\Db::name("wanlshop_advert")->where(["module" => 'other'])->select();
        $this->success('获取成功', $list);
    }

    public function getShareInfo() {
        $a = [];
        $getInfo = \think\Db::name('Zconfig')->where('key', 'index_share')->find();
        $getInfo2 = \think\Db::name('Zconfig')->where('key', 'normal_share')->find();
        $a["index_share"] = $getInfo;
        $a["normal"] = $getInfo2;
        $this->success('获取成功', $a);
    }

    public function funds() {

        $list = \think\Db::name("fenhong")->where("remain_fenhong", ">", 0)->select();
        $getRate = Tools::getConfig1("max_rate");
        $funds = Tools::getConfig1("Jackpot");
        $date = date('Y-m-d', time() - 10);
        $tomorrowyestoday_date = date('Y-m-d', time() - 86410);
        $yestoday = Db::name("fenhong_instance")->where(["calctime" => $date])->find();
        //昨日系统存在分红
        $fenhong = $yestoday["sys_fenhong"];
        $fhz = $funds / $fenhong;
        //系统设定分红值
        $System_FenHong = Db::name("zconfig")->where(["key" => $date])->find();
        foreach ($list as $key => $value) {
            
        }
    }

    public function getVersion() {
        $config = Tools::getConfig1('app_version,update_app_content,app_download_link');
        $config['type'] = 1;
        $config['link'] = $config['app_download_link'];
        $this->success('获取成功', $config);
    }

    /**
     * 获取APP版本号2,两个都在用
     */
    public function getVersion2() {
        $config = Tools::getConfig1('app_version,update_app_content,app_download_link');
        $config['VersionCode'] = 3;
        $config['DownloadUrl'] = $config['app_download_link'];
        $config['VersionName'] = $config['app_version'];
        $config['ModifyContent'] = $config['update_app_content'];
        $config['UpdateStatus'] = 2; //2是强制更新，1是非强制更新
        $config['ApkSize'] = 20480;
        $config['ApkMd5'] = '';
        $config['UploadTime'] = date('Y-m-d H:i:s');
        $config['Code'] = 0;
        $config['Msg'] = '';
        die(json_encode($config));
    }
    

}
