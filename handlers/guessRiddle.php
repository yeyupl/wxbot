<?php
/**
 * 猜谜语游戏
 * @author 罗会铸 yeyupl@qq.com
 */

namespace fw\service\wxBot\handlers;

use fw\service\wxBot\message\text;
use fw\service\wxBot\wxBot;


class guessRiddle {

    private static $riddles = [
        ['一点起飞（打一水果）', '龙眼'],
        ['不经一事不长一智（打一疾病名称）', '过敏'],
        ['十个男人看五个女人洗澡（打一成语）', '五光十色'],
        ['百万富翁看到地上的一毛钱（打一名人）', '布什'],
        ['72小时（打一字）', '晶'],
        ['备字有,飞字有,操字也有(打一字)', '德'],
        ['自觉吃药(打一成语)', '心服口服'],
        ['判决无罪（打一字）', '皓'],
        ['七个豆腐干，两个签签串 （打一字）', '拜'],
        ['爷孙双双回家来(打一成语)', '返老还童'],
    ];

    private static $hadGuess = [];

    /**
     * 获得一条谜语
     * @return mixed
     */
    private static function getRiddle() {
        $riddles = array_diff(static::$riddles, static::$hadGuess);
        if (!$riddles) {
            static::$hadGuess = [];
            $riddles = static::$riddles;
        }
        return $riddles[array_rand($riddles)];
    }

    public static function messageHandler($message) {
        if ($message['type'] === 'text' && $message['fromType'] === 'Group' && in_array($message['from']['NickName'], wxBot::getInstance()->config['groups'])) {
            $username = $message['from']['UserName'];

            $isBegin = isset(game::$games[$username]);

            if ($isBegin && game::$games[$username]['name'] != '猜谜语') {
                return false;
            }

            if ($message['pure'] === '猜谜语') {
                if ($isBegin) {
                    text::send($username, '游戏【猜谜语】正在进行中，看谁先猜对！' . PHP_EOL . game::$games[$username]['data'][0]);
                } else {
                    $riddle = static::getRiddle();
                    text::send($username, '【猜谜语】开始，看谁先猜对，格式：(猜 谜底)，猜错-5分，最先猜对的获得所有参与者消耗的积分！' . PHP_EOL . $riddle[0]);
                    game::$games[$username]['name'] = '猜谜语';
                    game::$games[$username]['data'] = $riddle;
                    game::$games[$username]['start_time'] = time();
                    game::$games[$username]['score'] = 0;
                }
                return true;
            } elseif (str_start_with($message['content'], '猜') && $isBegin) {

                if (!group::checkScore($message, 3)) {
                    text::send($username, '@' . $message['sender']['NickName'] . ' 积分不足，无法参与！');
                    return true;
                }

                $guess = trim(str_replace('猜', '', $message['content']));

                if ($guess == game::$games[$username]['data'][1]) {
                    if (game::$games[$username]['data']['score'] == 0) {
                        game::$games[$username]['data']['score'] = 10;
                    }
                    group::changeScore($message, game::$games[$username]['data']['score']);
                    text::send($username, '@' . $message['sender']['NickName'] . ' 你猜对了！谜底就是：' . $guess . '。积分+' . game::$games[$username]['data']['score'] . '，总积分 ' . group::getScore($message));
                    unset(game::$games[$username]);
                } else {
                    game::$games[$username]['data']['score'] += 5;
                    group::changeScore($message, -5);
                    text::send($username, '@' . $message['sender']['NickName'] . ' 你猜错了，积分-3，总积分 ' . group::getScore($message));

                }
                return true;
            } elseif ($isBegin && time() - game::$games[$username]['start_time'] >= 180) {
                text::send($username, '【猜谜语】游戏时间到了，自动结束本局游戏！谜底：' . game::$games[$username]['data'][1]);
                unset(game::$games[$username]);
            }
        }
        return false;
    }
}
