<?php

namespace qktong\cloudfile\model;

use think\Model;

class File extends Model
{
    public function __construct(array $data = [])
    {
        $this->connection = config('qiniu.database');
        parent::__construct($data);
    }

    public function addFile($user_id, $key, $hash, $extension, $size, $mime, $bucket)
    {
        $data                = [];
        $data['key']         = $key;
        $data['hash']        = $hash;
        $data['extension']   = $extension;
        $data['size']        = $size;
        $data['mime']        = $mime;
        $data['bucket']      = $bucket;
        $data['user_id'] = $user_id;

        return $this->insert($data);
    }

    public function getFileKey($key)
    {
        return $this->where(['key' => $key])->value('key');
    }

    public function getFileKeys($keys)
    {
        $list   = $this->field('key,bucket')->where('key', 'in', $keys)->select();
        $result = [];
        foreach ($list as $v) {
            $result[$v['key']] = $v['bucket'];
        }

        return $result;
    }

    public function incRepeatCount($key)
    {
        return $this->where(['key' => $key])->inc('repeat_count')->update();
    }

    public function getKeyByHash($hash)
    {
        return $this->where(['hash' => $hash])->value('key');
    }

    public function getInfoByKey($key)
    {
        return $this->field('id,create_time,update_time,time', true)->where(['key' => $key])->find();
    }

    public function getHash($hash)
    {
        return $this->where(['hash' => $hash])->value('hash');
    }

    public function getInfoByHash($hash)
    {
        return $this->field('id,create_time,update_time,time', true)->where(['hash' => $hash])->find();
    }

    public function hashContrast($data)
    {
        return $this->where('hash', $data)->value('id');
    }
}
