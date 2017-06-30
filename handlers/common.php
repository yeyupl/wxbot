<?php
/**
 * 公共消息响应
 * @author 罗会铸 yeyupl@qq.com
 */

namespace fw\service\wxBot\handlers;

use fw\service\wxBot\message\emoticon;
use fw\service\wxBot\message\image;
use fw\service\wxBot\message\text;
use fw\service\wxBot\message\video;
use fw\service\wxBot\message\voice;
use fw\service\wxBot\wxBot;

class common {

    /**
     * 消息响应
     * @param $message
     * @return bool
     */
    public static function messageHandler($message) {

        //点击事件消息
        if ($message['type'] === 'touch') {
            return true;
        }

        //撤回消息
        if ($message['type'] === 'recall') {
            text::send($message['from']['UserName'], $message['content'] . ' : ' . $message['origin']['content']);
            if ($message['origin']['type'] === 'image') {
                image::send($message['from']['UserName'], $message['origin']);
            } elseif ($message['origin']['type'] === 'emoticon') {
                emoticon::send($message['from']['UserName'], $message['origin']);
            } elseif ($message['origin']['type'] === 'video') {
                video::send($message['from']['UserName'], $message['origin']);
            } elseif ($message['origin']['type'] === 'voice') {
                voice::send($message['from']['UserName'], $message['origin']);
            }
            return true;
        }

        //公众号消息
        if ($message['type'] === 'official') {
            wxBot::console('收到公众号消息:' . $message['title'] . $message['description'] . $message['app'] . $message['url']);
            return true;
        }

        //自动斗图
        if ($message['type'] === 'emoticon' && $message['from']['NickName'] != 'Vbot 体验群') {
            emoticon::download($message);
            emoticon::sendRandom($message['from']['UserName']);
            return true;
        }

        if (in_array($message['content'], ['帮助', '菜单', '功能', 'help'])) {
            $helpText = '=====\ue23e 功能菜单 \ue23e=====' . PHP_EOL;
            $helpText .= '\ue210 全局功能：消息防撤回' . PHP_EOL;
            $helpText .= '\ue210 好友功能：自动通过好友/拉群/自动聊天' . PHP_EOL;
            $helpText .= '\ue210 群管功能：拉人/踢人/自动欢迎' . PHP_EOL . PHP_EOL;

            $helpText .= '\ue21c 签到：群签到' . PHP_EOL;
            $helpText .= '\ue21d 积分：查看自己积分' . PHP_EOL;
            $helpText .= '\ue21e 积分榜：查看群积分榜' . PHP_EOL;
            $helpText .= '\ue21f 增加积分：@昵称 +10' . PHP_EOL;

            $helpText .= '\ue220 游戏：游戏菜单' . PHP_EOL;
            $helpText .= '\ue221 群员对战：pk@昵称' . PHP_EOL;
            $helpText .= '\ue222 随机事件：聊天自动触发' . PHP_EOL;

            $helpText .= '\ue223 股票：股票功能菜单' . PHP_EOL;
            $helpText .= '\ue224 自动聊天：@机器人 聊天' . PHP_EOL;

            text::send($message['from']['UserName'], emojiDecode($helpText));
            return true;
        }

        return false;
    }

}

