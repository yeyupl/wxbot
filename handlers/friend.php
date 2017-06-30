<?php
/**
 * 好友消息响应
 * @author 罗会铸 yeyupl@qq.com
 */

namespace fw\service\wxBot\handlers;

use fw\service\wxBot\message\emoticon;
use fw\service\wxBot\message\file;
use fw\service\wxBot\message\image;
use fw\service\wxBot\message\text;
use fw\service\wxBot\message\video;
use fw\service\wxBot\message\voice;
use fw\service\wxBot\messageHandler;
use fw\service\wxBot\wxBot;
use fw\service\wxBot\task;


class friend {

    /**
     * 消息响应
     * @param $message
     * @return bool
     */
    public static function messageHandler($message) {

        $friends = wxBot::getInstance()->friends;
        $groups = wxBot::getInstance()->groups;


        if ($message['type'] === 'new_friend') {
            text::send($message['from']['UserName'], '客官，等你很久了！回复"拉我"我就会拉你进测试群！');
            return true;
        }

        if ($message['fromType'] === 'Friend' && $message['type'] === 'text' && $message['content'] === '拉我') {
            foreach (wxBot::getInstance()->config['groups'] as $groupName) {
                if ($groupName == '夜雨Bot体验群') {
                    $username = $groups->getUserNameByNickname($groupName);
                    $groups->addMember($username, $message['from']['UserName']);
                }
            }
            text::send($message['from']['UserName'], '现在拉你进去夜雨Bot体验群，进去后为了避免轰炸记得设置免骚扰哦！如果被不小心踢出群，跟我说声“拉我”我就会拉你进群的了。');
            return true;
        }

        if ($message['type'] === 'request_friend') {
            wxBot::console('收到好友申请:' . $message['info']['Content'] . $message['avatar']);
            //if ($message['info']['Content'] === 'echo') {
            $friends->approve($message);
            //}
            return true;
        }

        if ($message['fromType'] === 'Friend') {

            //测试用，不全部开放
            if (in_array($message['from']['NickName'], wxBot::getInstance()->config['administrator'])) {

                if (str_start_with($message['content'], '群发:')) {
                    $content = str_replace('群发:', '', $message['content']);
                    task::addTask($content);
                }

                if ($message['type'] === 'location') {
                    text::send($message['from']['UserName'], $message['content']);
                    text::send($message['from']['UserName'], $message['url']);
                    return true;
                }


                if ($message['type'] === 'image') {
                    image::download($message);
                    /*
                    image::download($message, function ($resource) {
                        file_put_contents(wxBot::getInstance()->config['user_path'] . '/image.jpg', $resource);
                    });
                    */
                    image::send($message['from']['UserName'], $message);
                    //image::send($message['from']['UserName'], __DIR__.'/test1.jpg');
                    return true;
                }

                if ($message['type'] === 'voice') {
                    voice::download($message);
                    //voice::download($message, function ($resource) {
                    //    file_put_contents(__DIR__.'/test1.mp3', $resource);
                    //});
                    voice::send($message['from']['UserName'], $message);
                    //voice::send($message['from']['UserName'], __DIR__.'/test1.mp3');
                    return true;
                }

                if ($message['type'] === 'video') {
                    video::download($message);
                    //video::download($message, function($resource){
                    //    file_put_contents(__DIR__.'/test1.mp4', $resource);
                    //});
                    video::send($message['from']['UserName'], $message);
                    //video::send($message['from']['UserName'], __DIR__.'/test1.mp4');
                    return true;
                }

                if ($message['type'] === 'emoticon') {
                    emoticon::download($message);
                    //video::download($message, function($resource){
                    //    file_put_contents(__DIR__.'/test1.mp4', $resource);
                    //});
                    //emoticon::send($message['from']['UserName'], $message);
                    emoticon::sendRandom($message['from']['UserName']);
                    return true;
                }


                if ($message['type'] === 'red_packet') {
                    text::send($message['from']['UserName'], $message['content']);
                    return true;
                }

                if ($message['type'] === 'transfer') {
                    text::send($message['from']['UserName'], $message['content'] . ' 转账金额： ' . $message['fee'] . ' 转账流水号：' . $message['transaction_id'] . ' 备注：' . $message['memo']);
                    return true;
                }

                if ($message['type'] === 'file') {
                    File::send($message['from']['UserName'], $message);
                    text::send($message['from']['UserName'], '收到文件：' . $message['title']);
                    return true;
                }

                if ($message['type'] === 'mina') {
                    text::send($message['from']['UserName'], '收到小程序：' . $message['title'] . $message['url']);
                    return true;
                }

                if ($message['type'] === 'share') {
                    text::send($message['from']['UserName'], '收到分享:' . $message['title'] . $message['description'] . $message['app'] . $message['url']);
                    return true;
                }

                if ($message['type'] === 'card') {
                    text::send($message['from']['UserName'], '收到名片:' . $message['avatar'] . $message['province'] . $message['city'] . $message['description']);
                    return true;
                }
            }

            //机器人自动聊天
            text::send($message['from']['UserName'], messageHandler::reply($message['content'], $message['from']['UserName']));
            return true;
        }

        return false;
    }

}

