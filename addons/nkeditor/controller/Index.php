<?php

namespace addons\nkeditor\controller;

use app\common\model\Attachment;
use GuzzleHttp\Client;
use think\addons\Controller;

class Index extends Controller
{

    public function index()
    {
        $this->error('该插件暂无前台页面');
    }

    /**
     * 文件列表
     */
    public function attachment()
    {
        $model = new Attachment;
        $page = $this->request->request('page');
        $fileType = $this->request->request('fileType');
        $module = $this->request->param('module');
        $pagesize = 15;
        $config = get_addon_config('nkeditor');
        $type = [];
        $imageSuffix = ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp'];
        if ($fileType == 'image') {
            $type = $imageSuffix;
        } else if ($fileType == 'flash') {
            $type = ['swf', 'flv'];
        } else if ($fileType == 'media') {
            $type = ['swf', 'flv', 'mp4', 'mpeg', 'mp3', 'wav', 'ogg', 'acc', 'webm'];
        } else if ($fileType == 'file') {

        }
        if ($module == 'admin') {
            $auth = \app\admin\library\Auth::instance();
            if (!$auth->id) {
                $this->error('请登录后再操作!');
            } else {
                $mode = $config['attachmentmode_admin'];
            }
            if ($mode == 'all') {

            } else {
                if (!$auth->isSuperAdmin()) {
                    $adminIds = $mode == 'auth' ? $auth->getChildrenAdminIds(true) : [$auth->id];
                    $model->where('admin_id', 'in', $adminIds);
                }
            }
        } else {
            if (!$this->auth->id) {
                $this->error('请登录后再操作!');
            } else {
                $mode = $config['attachmentmode_index'];
            }
            if ($mode == 'all') {

            } else {
                $model->where('user_id', 'in', [$this->auth->id]);
            }
        }

        if ($type) {
            $model->where('imagetype', 'in', $type);
        }

        $list = $model
            ->order('id', 'desc')
            ->paginate($pagesize);

        $items = $list->items();
        $data = [];
        foreach ($items as $k => &$v) {
            $v['imagetype'] = strtolower($v['imagetype']);
            $v['fullurl'] = cdnurl($v['url'], true);
            $data[] = [
                'width'    => $v['imagewidth'],
                'height'   => $v['imageheight'],
                'filesize' => $v['filesize'],
                'oriURL'   => $v['fullurl'],
                'thumbURL' => !in_array($v['imagetype'], $imageSuffix) ? "https://tool.fastadmin.net/icon/{$v['imagetype']}.png" : $v['fullurl'],
            ];
        }
        $result = [
            'code'     => '000',
            'count'    => $list->total(),
            'page'     => $page,
            'pagesize' => $pagesize,
            'extra'    => '',
            'data'     => $data
        ];
        return json($result);
    }

    public function download()
    {
        $url = $this->request->request("url");
        $client = new Client();
        $response = $client->get($url, ['verify' => false]);
        return $response->getBody();
    }

}
