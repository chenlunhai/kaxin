<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\Sms;
use fast\Random;
use think\Validate;
use Endroid\QrCode\QrCode;
use  think\Db;

/**
 * 会员接口
 */
class User extends Api {

    protected $noNeedLogin = ['login', 'mobilelogin', 'register', 'resetpwd', 'changeemail', 'changemobile', 'third'];
    protected $noNeedRight = '*';

    public function _initialize() {
        parent::_initialize();
    }

    /**
     * 会员中心
     */
    public function index() {
        $this->success('', ['welcome' => $this->auth->nickname]);
    }

    /**
     * 会员登录
     *
     * @param string $account  账号
     * @param string $password 密码
     */
    public function login() {
        $account = $this->request->request('account');
        $password = $this->request->request('password');
        if (!$account || !$password) {
            $this->error(__('Invalid parameters'));
        }
        $ret = $this->auth->login($account, $password);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 手机验证码登录
     *
     * @param string $mobile  手机号
     * @param string $captcha 验证码
     */
    public function mobilelogin() {
        $mobile = $this->request->request('mobile');
        $captcha = $this->request->request('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        if (!Sms::check($mobile, $captcha, 'mobilelogin')) {
            $this->error(__('Captcha is incorrect'));
        }
        $user = \app\common\model\User::getByMobile($mobile);
        if ($user) {
            if ($user->status != 'normal') {
                $this->error(__('Account is locked'));
            }
            //如果已经有账号则直接登录
            $ret = $this->auth->direct($user->id);
        } else {
            $ret = $this->auth->register($mobile, Random::alnum(), '', $mobile, []);
        }
        if ($ret) {
            Sms::flush($mobile, 'mobilelogin');
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 注册会员
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $email    邮箱
     * @param string $mobile   手机号
     * @param string $code   验证码
     */
    public function register() {
        $username = $this->request->request('username');
        $password = $this->request->request('password');
        $email = $this->request->request('email');
        $mobile = $this->request->request('mobile');
        $code = $this->request->request('code');
        $inviteCode = $this->request->request('invite');
        if (!$username || !$password) {
            $this->error(__('Invalid parameters'));
        }
        if ($email && !Validate::is($email, "email")) {
            $this->error(__('Email is incorrect'));
        }
        if ($mobile && !Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        $ret = Sms::check($mobile, $code, 'register');
        if (!$ret) {
            $this->error(__('Captcha is incorrect'));
        }
        $pids = $this->auth->existParent($inviteCode);
        if ($pids == 0) {
            $this->error(__('邀请码错误'));
        }
        $ret = $this->auth->register($mobile, Random::alnum(), '', $mobile, [], $pids);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Sign up successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 退出登录
     */
    public function logout() {
        $this->auth->logout();
        $this->success(__('Logout successful'));
    }

    /**
     * 修改会员个人信息
     *
     * @param string $avatar   头像地址
     * @param string $username 用户名
     * @param string $nickname 昵称
     * @param string $bio      个人简介
     */
    public function profile() {
        $user = $this->auth->getUser();
        $username = $this->request->request('username');
        $nickname = $this->request->request('nickname');
        $bio = $this->request->request('bio');
        $avatar = $this->request->request('avatar', '', 'trim,strip_tags,htmlspecialchars');
        if ($username) {
            $exists = \app\common\model\User::where('username', $username)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error(__('Username already exists'));
            }
            $user->username = $username;
        }
        if ($nickname) {
            $exists = \app\common\model\User::where('nickname', $nickname)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error(__('Nickname already exists'));
            }
            $user->nickname = $nickname;
        }
        $user->bio = $bio;
        $user->avatar = $avatar;
        $user->save();
        $this->success();
    }

    /**
     * 修改邮箱
     *
     * @param string $email   邮箱
     * @param string $captcha 验证码
     */
    public function changeemail() {
        $user = $this->auth->getUser();
        $email = $this->request->post('email');
        $captcha = $this->request->request('captcha');
        if (!$email || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::is($email, "email")) {
            $this->error(__('Email is incorrect'));
        }
        if (\app\common\model\User::where('email', $email)->where('id', '<>', $user->id)->find()) {
            $this->error(__('Email already exists'));
        }
        $result = Ems::check($email, $captcha, 'changeemail');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
        $verification = $user->verification;
        $verification->email = 1;
        $user->verification = $verification;
        $user->email = $email;
        $user->save();

        Ems::flush($email, 'changeemail');
        $this->success();
    }

    /**
     * 修改手机号
     *
     * @param string $mobile   手机号
     * @param string $captcha 验证码
     */
    public function changemobile() {
        $user = $this->auth->getUser();
        $mobile = $this->request->request('mobile');
        $captcha = $this->request->request('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        if (\app\common\model\User::where('mobile', $mobile)->where('id', '<>', $user->id)->find()) {
            $this->error(__('Mobile already exists'));
        }
        $result = Sms::check($mobile, $captcha, 'changemobile');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
        $verification = $user->verification;
        $verification->mobile = 1;
        $user->verification = $verification;
        $user->mobile = $mobile;
        $user->save();

        Sms::flush($mobile, 'changemobile');
        $this->success();
    }

    /**
     * 返回邀请码和链接
     */
    public function share() {
         require '../vendor/qrcode.php';

        $uid = $this->request->post('uid');

        //$useobj =
        $user =Db::name("user")->where(["id"=>$uid])->find();

//        $value = 'http://' . $_SERVER['HTTP_HOST'] . '/index/UserNt/register?uid=' . $uid;         //二维码内容
        // $value = 'https://' . $_SERVER['HTTP_HOST'] . '/index/User/register?uid=' . $uid;         //二维码内容
        $value = 'https://h5.youmeihui168.cc/#/pages/user/auth/register?from=h5&invite=' . $user['recommend'] . '&cmobile=' . $user['mobile'];         //二维码内容
        $errorCorrectionLevel = 'L';    //容错级别
        $matrixPointSize = 7;            //生成图片大小
        //生成二维码图片
        // $filename = __DIR__ . '/../../../public/';
        // $filename = ROOT_PATH.'public/';
        // $filename2 = 'uploads/share/share-' . $uid . '.png';

        $filename = ROOT_PATH.'public/';
        $filename2 = 'assets/addons/wanlshop/img/qrcode/share-' . $uid . '.png';
        
        $filename .= $filename2;
        if (!file_exists($filename)) {
            \QRcode::png($value, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
            
            // $user = model('Zuser')->where('id=:id', ['id' => $uid])->field('mobile,name')->find();
            
//        echo "<img src='http://" . $_SERVER['HTTP_HOST'] . "/{$filename2}'>";
            // $img1 = imagecreatefromjpeg(__DIR__ . '/../../../public/static/sif/images/invitation.png');
            // $img2 = imagecreatefrompng(__DIR__ . '/../../../public/' . $filename2);
            // $img1 = imagecreatefromjpeg(ROOT_PATH.'public/static/sif/images/invitation.png');
            $img1 = imagecreatefromjpeg(ROOT_PATH . '/public/assets/addons/wanlshop/img/default/tg.jpg');
            $img2 = imagecreatefrompng(ROOT_PATH.'public/' . $filename2);
            // imagecopyresampled($img1, $img2, 300, 400, 0, 0, 500, 500, 230, 230);//205, 430, 0, 0, 350, 350, 235, 235
            imagecopyresampled($img1, $img2, 90, 1529, 0, 0, 310, 310, 280, 280);//205, 430, 0, 0, 350, 350, 235, 235  310, 310, 280, 280
            // imagettftext($img1, 40, 0, 380, 970, imagecolorallocate($img1, 255, 203, 65), __DIR__ . '/../../../public/assets/fonts/msyh.ttf', $user['mobile']);
            // imagettftext($img1, 50, 0, 380, 1070, imagecolorallocate($img1, 255, 203, 65), __DIR__ . '/../../../public/assets/fonts/msyh.ttf', '邀请您加入');
             imagettftext($img1, 36, 0, 810, 1740, imagecolorallocate($img1, 255, 255, 255), ROOT_PATH.'public//assets/fonts/msyh.ttf', $user['recommend']);
             //imagettftext($img1, 50, 0, 380, 1070, imagecolorallocate($img1, 255, 203, 65), ROOT_PATH.'public/assets/fonts/msyh.ttf', '邀请您加入');
            imagepng($img1, $filename);
        }
        //$user = Redis::readRedis('Zuser', $uid, 'mobile,name');
        $data = [
            'link' => $value,
            'img' => "http://" . $_SERVER['HTTP_HOST'] . "/{$filename2}".'?v='.time(),
            'recommend' => $user['recommend'],
//            'mobile' => substr($user['mobile'], 0, 3) . '****' . substr($user['mobile'], 7, 4),
//            'incode' => $user['name'],
        ];
//        if (strlen($data['incode']) == 11) {
//            $data['incode'] = substr($user['mobile'], 0, 3) . '****' . substr($user['mobile'], 7, 4);
//        }
        $this->success('获取成功', $data);
    }

    /**
     * 第三方登录
     *
     * @param string $platform 平台名称
     * @param string $code     Code码
     */
    public function third() {
        $url = url('user/index');
        $platform = $this->request->request("platform");
        $code = $this->request->request("code");
        $config = get_addon_config('third');
        if (!$config || !isset($config[$platform])) {
            $this->error(__('Invalid parameters'));
        }
        $app = new \addons\third\library\Application($config);
        //通过code换access_token和绑定会员
        $result = $app->{$platform}->getUserInfo(['code' => $code]);
        if ($result) {
            $loginret = \addons\third\library\Service::connect($platform, $result);
            if ($loginret) {
                $data = [
                    'userinfo' => $this->auth->getUserinfo(),
                    'thirdinfo' => $result
                ];
                $this->success(__('Logged in successful'), $data);
            }
        }
        $this->error(__('Operation failed'), $url);
    }

    /**
     * 重置密码
     *
     * @param string $mobile      手机号
     * @param string $newpassword 新密码
     * @param string $captcha     验证码
     */
    public function resetpwd() {
        $type = $this->request->request("type");
        $mobile = $this->request->request("mobile");
        $email = $this->request->request("email");
        $newpassword = $this->request->request("newpassword");
        $captcha = $this->request->request("captcha");
        if (!$newpassword || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if ($type == 'mobile') {
            if (!Validate::regex($mobile, "^1\d{10}$")) {
                $this->error(__('Mobile is incorrect'));
            }
            $user = \app\common\model\User::getByMobile($mobile);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Sms::check($mobile, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Sms::flush($mobile, 'resetpwd');
        } else {
            if (!Validate::is($email, "email")) {
                $this->error(__('Email is incorrect'));
            }
            $user = \app\common\model\User::getByEmail($email);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Ems::check($email, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Ems::flush($email, 'resetpwd');
        }
        //模拟一次登录
        $this->auth->direct($user->id);
        $ret = $this->auth->changepwd($newpassword, '', true);
        if ($ret) {
            $this->success(__('Reset password successful'));
        } else {
            $this->error($this->auth->getError());
        }
    }

}
