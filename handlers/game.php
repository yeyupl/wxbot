<?php
/**
 * 游戏
 * @author 罗会铸 yeyupl@qq.com
 */

namespace fw\service\wxBot\handlers;

use fw\service\wxBot\message\text;
use fw\service\wxBot\wxBot;


class game {

    public static $games = [];

    public static function messageHandler($message) {
        if ($message['type'] === 'text' && $message['fromType'] === 'Group' && in_array($message['from']['NickName'], wxBot::getInstance()->config['groups'])) {
            if ($message['content'] == '游戏') {
                $msg = '=====\ue310 游 戏 \ue310=====' . PHP_EOL;
                $msg .= '\ue21c 猜数字' . PHP_EOL;
                $msg .= '\ue21d 猜大小' . PHP_EOL;
                $msg .= '\ue21e 猜谜语' . PHP_EOL;
                $msg .= '\ue21f 幸运28' . PHP_EOL;
                $msg .= '\ue220 投票' . PHP_EOL;
                //$msg .= '\ue221 调查' . PHP_EOL;

                $msg .= PHP_EOL . '\ue325提示：请输入游戏名称开始游戏，如 幸运28' . PHP_EOL;
                $msg .= '\ue252 声明：游戏和积分仅供娱乐，勿作非法用途，由此产生一切后果与开发者无关！';

                text::send($message['from']['UserName'], emojiDecode($msg));
                return true;
            }
            $result = guessNumber::messageHandler($message)
                || guessSize::messageHandler($message)
                || guessRiddle::messageHandler($message)
                || guessLuck28::messageHandler($message)
                || vote::messageHandler($message)
                || survey::messageHandler($message);

            return $result;
        }
    }

}
