<?php

namespace qktong\cloudfile;

use qktong\cloudfile\model\File;

class FileService extends BaseService
{
    public function getFileUrl($keys, $style)
    {
        $keys = explode(',', $keys);
        $size = sizeof($keys);
        if ($size > 100) {
            return $this->error(20602);
        }
        $file  = new File();
        $qiniu = new QiniuService();
        // 校验key是否存在
        $key_list = $file->getKeys($keys);

        $result = [];
        foreach ($keys as $file_key) {
            if (isset($key_list[$file_key])) {
                $result[$file_key] = $qiniu->getFileUrl($file_key, $key_list[$file_key], $style);
            } else {
                $result[$file_key] = null;
            }
        }

        return $result;
    }

    public function upload($user_id, $extension, $file_name, $mime, &$streamData, $bucket)
    {
        if ($file_name) {
            $key = $file_name;
        } elseif ($extension) {
            $key = date('ymdHi') . uniqid() . '.' . $extension;
        } else {
            $key = date('ymdHi') . uniqid();
        }
        $qiniu            = new QiniuService();
        list($hash, $err) = $qiniu->getHash($streamData);
        if (null !== $err) {
            return $this->error(20600, $err);
        }
        $file = new File();
        // 文件名重复
        if ($file_name and $file->getKey($key)) {
            return $this->error(20601, $key);
        }

        // 重复的文件不上传 但如果重命名上传
        $historyKey = $file->getKeyByHash($hash);
        if (empty($file_name) and $historyKey) {
            $file->incRepeatCount($historyKey);

            return $historyKey;
        }

        $qiniu            = new QiniuService();
        list($info, $err) = $qiniu->upload($key, $streamData, $bucket, $mime);
        if (null !== $err) {
            return $this->error(20600, $err);
        }
        $size = strlen($streamData);
        $file->addFile($user_id, $key, $hash, $extension, $size, $mime, $bucket);

        return $key;
    }

    public function del($keys, $bucket)
    {
        $qiniu  = new QiniuService();
        $result = $qiniu->del($keys, $bucket);

        return $result;
    }

    public function hashContrast($data)
    {
        $qiniu            = new QiniuService();
        list($hash, $err) = $qiniu->getHash($data);
        $file             = new File();
        $result           = $file->hashContrast($hash);
        if (empty($result)) {
            return true;
        }

        return false;
    }
}
