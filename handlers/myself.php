<?php
/**
 * 自身消息响应
 * @author 罗会铸 yeyupl@qq.com
 */

namespace fw\service\wxBot\handlers;

use fw\service\wxBot\task;

class myself {

    /**
     * 自己发送的消息,优先级最高，指令处理
     * @param $message
     * @return bool
     */
    public static function messageHandler($message) {
        if ($message['fromType'] == 'Self') {
            if ($message['content'] == 'exit') {
                exec('kill -9 ' . getmypid());
            }
            if (str_start_with($message['content'], '群发:')) {
                $content = str_replace('群发:', '', $message['content']);
                task::addTask($content);
            }
        }
        return false;
    }
}
