<?php
/**
 * 猜大小游戏
 * @author 罗会铸 yeyupl@qq.com
 */

namespace fw\service\wxBot\handlers;

use fw\service\wxBot\message\text;
use fw\service\wxBot\wxBot;


class guessSize {

    public static function messageHandler($message) {
        if ($message['type'] === 'text' && $message['fromType'] === 'Group' && in_array($message['from']['NickName'], wxBot::getInstance()->config['groups'])) {
            $username = $message['from']['UserName'];

            $isBegin = isset(game::$games[$username]);

            if ($isBegin && game::$games[$username]['name'] != '猜大小') {
                return false;
            }

            if ($message['pure'] === '猜大小') {
                if ($isBegin) {
                    text::send($username, '游戏【猜大小】正在进行中，每人限猜一次！');
                } else {
                    text::send($username, '【猜大小】开始，请猜大或小，猜对+5分，猜错-5分，倍投(如 大*5)，由@' . $message['sender']['NickName'] . ' 负责开奖。');
                    game::$games[$username]['owner'] = $message['sender']['NickName'];
                    game::$games[$username]['name'] = '猜大小';
                    game::$games[$username]['data'] = [
                        'target' => mt_rand(1, 10) <= 5 ? '小' : '大',
                    ];
                    game::$games[$username]['start_time'] = time();
                }
                return true;
            } elseif ((in_array($message['content'], ['大', '小']) || str_start_with($message['content'], '大*') || str_start_with($message['content'], '小*')) && $isBegin) {
                if (isset(game::$games[$username]['result'][$message['sender']['UserName']])) {
                    text::send($username, '@' . $message['sender']['NickName'] . ' 你已猜：' . game::$games[$username]['result'][$message['sender']['UserName']]['content'] . '，请等待开奖！');
                    return true;
                }
                $times = 1;
                $guess = $message['content'];
                if (str_contains($message['content'], '*')) {
                    $array = explode('*', $message['content']);
                    $times = max(1, intval($array[1]));
                    $guess = trim($array[0]);
                }
                if (!group::checkScore($message, 5 * $times)) {
                    text::send($username, '@' . $message['sender']['NickName'] . ' 积分不足，无法参与！');
                    return true;
                }
                game::$games[$username]['result'][$message['sender']['UserName']] = [
                    'guess' => $guess,
                    'content' => $message['content'],
                    'times' => $times,
                    'nickName' => $message['sender']['NickName']
                ];
                group::changeScore($message, -5 * $times);
                text::send($username, '@' . $message['sender']['NickName'] . ' 猜：' . $message['content'] . '，买定离手');
                return true;
            } elseif ($isBegin && (($message['sender']['NickName'] == game::$games[$username]['owner'] && $message['content'] == '开奖') || time() - game::$games[$username]['start_time'] >= 180)) {
                if (count(game::$games[$username]['result']) == 0) {
                    Text::send($username, '当前无人参与本轮【猜大小】游戏，游戏结束！');
                    unset(game::$games[$username]);
                    return true;
                }
                $msg = '【猜大小】开奖结果为：' . game::$games[$username]['data']['target'] . PHP_EOL;
                $result = array();
                foreach (game::$games[$username]['result'] as $sender => $val) {
                    if ($val['guess'] == game::$games[$username]['data']['target']) {
                        group::changeScore($message, 10 * $val['times'], $val['nickName']);
                        $result[] = $val['nickName'] . ' 【' . $val['content'] . '】 积分+' . (5 * $val['times']) . '，总积分 ' . group::getScore($message, $val['nickName']);
                    } else {
                        $result[] = $val['nickName'] . ' 【' . $val['content'] . '】  积分-' . (5 * $val['times']) . '，总积分 ' . group::getScore($message, $val['nickName']);
                    }
                }
                $msg .= implode(PHP_EOL, $result);
                Text::send($username, $msg);
                unset(game::$games[$username]);
                return true;
            }
        }
        return false;
    }
}
