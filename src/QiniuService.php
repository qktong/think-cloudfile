<?php

namespace qktong\cloudfile;

use Qiniu\Auth;
use Qiniu\Config;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\FormUploader;
use Qiniu\Storage\ResumeUploader;
use Qiniu\Storage\UploadManager;

class QiniuService
{
    private $config;

    /**
     * Qiniu constructor.
     *
     * @param $config
     */
    public function __construct()
    {
        $this->config = config('qiniu');
    }

    /**
     * 文件上传.
     *
     * @param string $key        上传文件名 唯一值
     * @param string $data       文件的二进制流
     * @param string $bucket     存储的空间
     * @param string $mime       上传数据的mimeType
     * @param string $params     自定义变量，规格参考
     *                           http://developer.qiniu.com/docs/v6/api/overview/up/response/vars.html#xvar
     * @param mixed  $streamData
     *
     * @return mixed
     */
    public function upload($key, &$streamData, $bucket, $mime = 'application/octet-stream', $params = [])
    {
        $accessKey = $this->config['accessKey'];
        $secretKey = $this->config['secretKey'];
        if (! isset($this->config['bucket'][$bucket])) {
            throw new \Exception('bucket 没有配置', 1);
        }
        $upManager = new UploadManager();
        $auth      = new Auth($accessKey, $secretKey);
        $upToken   = $auth->uploadToken($this->config['bucket'][$bucket]['name']);

        $file = fopen('php://memory', 'r+');
        if (false === $file) {
            throw new \Exception('file can not open', 1);
        }
        fputs($file, $streamData);
        rewind($file);
        $params = UploadManager::trimParams($params);
        $stat   = fstat($file);
        $size   = $stat['size'];
        if ($size <= Config::BLOCK_SIZE) {
            $data = fread($file, $size);
            if (false === $data) {
                throw new \Exception('file can not read', 1);
            }
            $ret = FormUploader::put(
                $upToken,
                $key,
                $data,
                new Config(),
                $params,
                $mime,
                $key
            );
            fclose($file);

            return $ret;
        }

        $up = new ResumeUploader(
            $upToken,
            $key,
            $file,
            $size,
            $params,
            $mime,
            new Config()
            );
        $ret = $up->upload($key);
        fclose($file);

        return $ret;
    }

    public function getHash(&$streamData)
    {
        $fhandler = fopen('php://memory', 'r+');

        fputs($fhandler, $streamData);
        rewind($fhandler);

        $err = error_get_last();
        if (null !== $err) {
            return [null, $err];
        }

        $fstat = fstat($fhandler);
        $fsize = $fstat['size'];
        if (0 === (int) $fsize) {
            fclose($fhandler);

            return ['Fto5o-5ea0sNMlW_75VgGJCv2AcJ', null];
        }
        $blockCnt = intval(($fsize + (Config::BLOCK_SIZE - 1)) / Config::BLOCK_SIZE);
        $sha1Buf  = [];
        if ($blockCnt <= 1) {
            array_push($sha1Buf, 0x16);
            $fdata = fread($fhandler, Config::BLOCK_SIZE);
            if (null !== $err) {
                fclose($fhandler);

                return [null, $err];
            }
            list($sha1Code) = self::calcSha1($fdata);
            $sha1Buf        = array_merge($sha1Buf, $sha1Code);
        } else {
            array_push($sha1Buf, 0x96);
            $sha1BlockBuf = [];
            for ($i = 0; $i < $blockCnt; ++$i) {
                $fdata                = fread($fhandler, Config::BLOCK_SIZE);
                list($sha1Code, $err) = self::calcSha1($fdata);
                if (null !== $err) {
                    fclose($fhandler);

                    return [null, $err];
                }
                $sha1BlockBuf = array_merge($sha1BlockBuf, $sha1Code);
            }
            $tmpData         = call_user_func_array('pack', array_merge(['C*'], (array) $sha1BlockBuf));
            list($sha1Final) = self::calcSha1($tmpData);
            $sha1Buf         = array_merge($sha1Buf, $sha1Final);
        }
        $tmpData = call_user_func_array('pack', array_merge(['C*'], (array) $sha1Buf));
        $etag    = \Qiniu\base64_urlSafeEncode($tmpData);

        return [$etag, null];
    }

    /**
     * 获取文件地址
     *
     * @param string $file
     * @param string $style
     *
     * @return mixed
     */
    public function getFileUrl($file, $bucket, $style = '')
    {
        $domain = $this->getDomain($bucket);
        $url    = $domain . $file;

        if (! empty($style)) {
            $url = $url . $this->config['style_separator'] . $style;
        }

        return $url;
    }

    /**
     * 获取文件下载地址
     *
     * @param string $file
     * @param int    $expires
     *
     * @return mixed
     */
    public function getFileDownloadUrl($file, $expires = 3600)
    {
        $accessKey = $this->config['accessKey'];
        $secretKey = $this->config['secretKey'];
        $auth      = new Auth($accessKey, $secretKey);
        $url       = $this->getUrl($file);

        return $auth->privateDownloadUrl($url, $expires);
    }

    /**
     * 获取云存储域名.
     *
     * @return mixed
     */
    private function getDomain($bucket)
    {
        $domain = $this->config['bucket'][$bucket]['domain'] ?? '';
        if (empty($domain)) {
            throw new \Exception('未配置域名', 1);
        }

        return $domain;
    }

    /**
     * 获取文件相对上传目录路径.
     *
     * @param string $url
     *
     * @return mixed
     */
    public function getFilePath($url)
    {
        $parsedUrl = parse_url($url);

        if (! empty($parsedUrl['path'])) {
            $url            = ltrim($parsedUrl['path'], '/\\');
            $config         = $this->config;
            $styleSeparator = $config['style_separator'];

            $styleSeparatorPosition = strpos($url, $styleSeparator);
            if (false !== $styleSeparatorPosition) {
                $url = substr($url, 0, strpos($url, $styleSeparator));
            }
        } else {
            $url = '';
        }

        return $url;
    }

    // 七牛sdk Etag类里的函数
    private static function calcSha1($data)
    {
        $sha1Str = sha1($data, true);
        $err     = error_get_last();
        if (null !== $err) {
            return [null, $err];
        }
        $byteArray = unpack('C*', $sha1Str);

        return [$byteArray, null];
    }

    //删除七牛云上的文件
    public function del($file, $bucket = 'caopanshou')
    {
        $accessKey = $this->config['accessKey'];
        $secretKey = $this->config['secretKey'];
        if (! isset($this->config['bucket'][$bucket])) {
            throw new \Exception('bucket 没有配置', 1);
        }
        $auth          = new Auth($accessKey, $secretKey);
        $config        = new config();
        $bucketManager = new BucketManager($auth, $config);
        $result        = $bucketManager->delete($bucket, $file);

        return $result;
    }
}
