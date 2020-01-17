<?php

use qktong\message\Message;
use qktong\message\Sender;

class Index
{
    public function send()
    {
        $sender = new Sender();
        $r      = $sender->send(1, '18581037340', 'yzm', ['code' => 'csiw3'], 1);
        var_dump($r);
    }

    public function getList()
    {
        $msg  = new Message();
        $list = $msg->getList(1, [1], 1, 10);
        print_r($list);
    }

    public function readMessage()
    {
        $msg    = new Message();
        $result = $msg->readMessage(1, 1);
        var_dump($result);
    }

    public function readAllMessage()
    {
        $msg    = new Message();
        $result = $msg->readAllMessage(1);
        var_dump($result);
    }

    public function deleteMessage()
    {
        $msg    = new Message();
        $result = $msg->deleteMessage(1, 1);
        var_dump($result);
    }
}
