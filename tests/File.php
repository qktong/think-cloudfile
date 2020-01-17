<?php

namespace app\base\controller;

use qktong\cloudfile\FileService;
use app\common\controller\BaseController;

class File extends BaseController
{
    public function upload()
    {
        $user_id    = input('get.user_id', false, 0);
        $extension  = input('get.extension', false, '');
        $file_name  = input('get.file_name', false, '');
        $bucket     = input('get.bucket', true, '');
        $mime       = input('get.mime', true, '', 'regex:/^[a-zA-Z0-9\/\-]+$/u');
        $streamData = file_get_contents('php://input');

        $fileService = new FileService();
        $key         = $fileService->upload($user_id, $extension, $file_name, $mime, $streamData, $bucket);

        return $this->succ(['file_key' => $key]);
    }

    public function getFileUrl()
    {
        $keys        = input('post.keys', true, '', 'regex:/([^\\/]+)\.?([^\\/]+)+,?/u');
        $style       = input('post.style', false, '');
        $fileService = new FileService();
        $result      = $fileService->getFileUrl($keys, $style);

        return $this->succ($result);
    }
}
