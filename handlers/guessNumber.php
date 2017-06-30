<?php
/**
 * 猜数字游戏
 * @author 罗会铸 yeyupl@qq.com
 */

namespace fw\service\wxBot\handlers;

use fw\service\wxBot\message\text;
use fw\service\wxBot\wxBot;


class guessNumber {

    public static function messageHandler($message) {
        if ($message['type'] === 'text' && $message['fromType'] === 'Group' && in_array($message['from']['NickName'], wxBot::getInstance()->config['groups'])) {
            $username = $message['from']['UserName'];

            $isBegin = isset(game::$games[$username]);

            if ($isBegin && game::$games[$username]['name'] != '猜数字') {
                return false;
            }

            if ($message['pure'] === '猜数字') {
                if ($isBegin) {
                    text::send($username, '游戏【猜数字】正在进行中，当前区间为：' . game::$games[$username]['data']['begin'] . ' 到 ' . game::$games[$username]['data']['end']);
                } else {
                    text::send($username, '【猜数字】开始，请猜一个 1 ~ 99 的数字，猜错-3分，最先猜对的获得所有参与者消耗的积分！');
                    game::$games[$username]['name'] = '猜数字';
                    game::$games[$username]['data'] = [
                        'begin' => 0,
                        'end' => 100,
                        'target' => mt_rand(1, 99),
                        'score' => 0
                    ];
                    game::$games[$username]['start_time'] = time();
                }
                return true;
            } elseif (is_numeric($message['content']) && $isBegin) {
                if (!group::checkScore($message, 3)) {
                    text::send($username, '@' . $message['sender']['NickName'] . ' 积分不足，无法参与！');
                    return true;
                }
                $message['content'] = intval($message['content']);
                $begin = game::$games[$username]['data']['begin'];
                $end = game::$games[$username]['data']['end'];
                $target = game::$games[$username]['data']['target'];

                if ($message['content'] > $begin && $message['content'] < $end) {
                    if ($message['content'] == $target) {
                        if (game::$games[$username]['data']['score'] == 0) {
                            game::$games[$username]['data']['score'] = 10;
                        }
                        group::changeScore($message, game::$games[$username]['data']['score']);
                        text::send($username, '@' . $message['sender']['NickName'] . ' 你猜对了！数字就是：' . $target . '。积分+' . game::$games[$username]['data']['score'] . '，总积分 ' . group::getScore($message));
                        unset(game::$games[$username]);
                    } elseif ($message['content'] > $target) {
                        group::changeScore($message, -3);
                        game::$games[$username]['data']['score'] += 3;
                        text::send($username, '@' . $message['sender']['NickName'] . ' 当前区间为：' . $begin . ' 到 ' . $message['content'] . '。积分-3，总积分 ' . group::getScore($message));
                        game::$games[$username]['data']['end'] = $message['content'];
                    } else {
                        group::changeScore($message, -3);
                        game::$games[$username]['data']['score'] += 3;
                        text::send($username, '@' . $message['sender']['NickName'] . ' 当前区间为：' . $message['content'] . ' 到 ' . $end . '。积分-3，总积分 ' . group::getScore($message));
                        game::$games[$username]['data']['begin'] = $message['content'];
                    }
                }
                return true;
            } elseif ($isBegin && time() - game::$games[$username]['start_time'] >= 180) {
                text::send($username, '【猜数字】游戏时间到了，自动结束本局游戏！');
                unset(game::$games[$username]);
            }
        }
        return false;
    }
}
