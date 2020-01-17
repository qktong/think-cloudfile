## 环境需求

- PHP >= 7.1
- thinkphp >= 6.0

## 介绍

基于thinkphp6,上传文件至七牛云

## 安装

```shell
composer require "qktong/cloudfile"
```

## 使用

```php
//上传文件
use qktong\cloudfile\FileService;
$fileService = new FileService();
$user_id    = '8';
$file_name  = 'test':
$extension  = 'jpg';
$mime       = 'image/jpeg';
$bucket     = 'test';
$streamData = file_get_contents('php://input');
$key        = $fileService->upload($user_id, $extension, $file_name, $mime, $streamData, $bucket);
```

```php
//上传文件
use qktong\cloudfile\FileService;
$keys = 'test.jpg';
$fileService = new FileService();
$result      = $fileService->getFileUrl($keys,'');
```