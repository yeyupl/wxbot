<?php
/**
 * 群组
 * @author 罗会铸 yeyupl@qq.com
 */

namespace fw\service\wxBot\handlers;

use fw\service\wxBot\message\text;
use fw\service\wxBot\messageHandler;
use fw\service\wxBot\wxBot;


class group {

    public static $score = [];

    private static $signIn = [];

    private static $events = [
        ['text' => '走路看手机，踩到一坨狗屎', 'score' => 1],
        ['text' => '骑小黄车撞到一个老太太，被讹了一笔', 'score' => -3],
        ['text' => '去大保健，被police抓了，破财消灾', 'score' => -5],
        ['text' => '善心突发，给天桥底下的乞丐投了一个硬币', 'score' => 3],
        ['text' => '手速超群，在公司群里抢到了老板发的1元红包', 'score' => 1],
        ['text' => '在公交车上给孕妇让座，弘扬中华传统美德', 'score' => 5],
        ['text' => '找一漂亮妹子要微信，被婉拒了', 'score' => -1],
        ['text' => '买的股票涨停了，要赚吐了', 'score' => 5],
        ['text' => '买的股票跌停了，亏惨了', 'score' => -5],
        ['text' => '扶老太太过马路不留名，请叫他雷锋', 'score' => 3],
        ['text' => '随地吐痰，毫无公德心', 'score' => -3]
    ];

    public static function messageHandler($message) {
        if ($message['type'] === 'text' && $message['fromType'] === 'Group' && in_array($message['from']['NickName'], wxBot::getInstance()->config['groups'])) {
            if ($message['content'] == '积分') {
                text::send($message['from']['UserName'], '@' . static::getDisplayName($message) . ' 总积分：' . static::getScore($message));
                return true;
            }

            if (in_array($message['content'], ['积分排行', '积分榜'])) {
                text::send($message['from']['UserName'], static::getScoreRank($message['from']['NickName']));
                return true;
            }

            if ($message['content'] === '签到') {
                if (static::signIn($message)) {
                    text::send($message['from']['UserName'], '@' . static::getDisplayName($message) . ' 签到成功！' . PHP_EOL . '积分+1，总积分 ' . static::getScore($message));
                } else {
                    text::send($message['from']['UserName'], '@' . static::getDisplayName($message) . ' 你今天已经签到过了，明天再来吧！');
                }
                return true;
            }

            if ($message['type'] === 'group_change') {
                if ($message['action'] === 'ADD') {
                    $msg = '╒═══进 群 提 示══╗' . PHP_EOL;
                    $msg .= '\ue325启禀群主,有一新人入群' . PHP_EOL;
                    $msg .= '\ue229昵  称：' . $message['invited'] . PHP_EOL;
                    $msg .= '\ue201邀请人：' . ($message['inviter'] == '你' ? wxBot::getInstance()->myself->nickname : $message['inviter']) . PHP_EOL;
                    $msg .= '\ue152提示：请遵守群规，勿发广告' . PHP_EOL;
                    $msg .= '\ue315发送 功能 即可获取功能列表!' . PHP_EOL;
                    $msg .= '╘══【进 群 提 示】══╝';

                    text::send($message['from']['UserName'], emojiDecode($msg));
                    return true;
                }
            }

            if ($message['content'] === '踢我') {
                wxBot::getInstance()->groups->deleteMember($message['from']['UserName'], $message['sender']['UserName']);
                return true;
            }

            if ($message['content'] === '叫我') {
                text::send($message['from']['UserName'], '@' . static::getDisplayName($message) . '，我是你最忠实的奴仆！');
                return true;
            }

            if ($message['content'] == '群规') {
                text::send($message['from']['UserName'], '拳打矮挫穷，脚踢土肥圆；' . PHP_EOL . '争做高富帅，迎娶白富美！');
                return true;
            }

            if ($message['fromType'] == 'Self' || in_array($message['sender']['NickName'], wxBot::getInstance()->config['administrator'])) {
                if (str_contains($message['content'], '踢')) {
                    $isDelete = false;
                    $deleteName = str_replace(['踢', '@'], '', $message['content']);
                    foreach ($message['from']['MemberList'] as $member) {
                        if ($member['NickName'] == $deleteName || $member['DisplayName'] == $deleteName) {
                            wxBot::getInstance()->groups->deleteMember($message['from']['UserName'], $member['UserName']);
                            text::send($message['from']['UserName'], $deleteName . '，永远的离我们而去了，全体默哀3秒...');
                            $isDelete = true;
                        }
                    }
                    if (!$isDelete) {
                        text::send($message['from']['UserName'], $deleteName . ' 是火星人吧，没在群里呢！');
                    }
                    return true;
                } elseif (str_start_with($message['content'], '拉')) {
                    $addName = str_replace(['拉', '@'], '', $message['content']);
                    if (!$addUserName = wxBot::getInstance()->friends->getUserNameByNickname($addName)) {
                        $addUserName = wxBot::getInstance()->friends->getUserNameByRemarkName($addName);
                    }
                    if ($addUserName) {
                        wxBot::getInstance()->groups->addMember($message['from']['UserName'], $addUserName);
                    } else {
                        text::send($message['from']['UserName'], '好友列表里没有 @' . $addName . ' 这号人物！');
                    }
                    return true;
                }
            }

            if (str_start_with($message['content'], '@') && str_contains($message['content'], '+') && $message['sender']['NickName'] == '夜雨') {
                $array = explode('+', $message['content']);
                $name = trim(str_replace('@', '', $array[0]));
                $score = max(1, intval($array[1]));
                if ($nickName = static::getNickName($message, $name)) {
                    static::changeScore($message, $score, $nickName);
                    text::send($message['from']['UserName'], '@' . $nickName . ' 积分 +' . $score . '，总积分 ' . static::getScore($message, $nickName));
                    return true;
                }
            }

            if (str_start_with(strtoupper($message['content']), 'PK') && str_contains($message['content'], '@')) {
                $name = trim(str_replace(['pk', 'PK', 'pK', 'Pk', '@'], '', $message['content']));
                if ($nickName = static::getNickName($message, $name)) {
                    $player1 = ['name' => $message['sender']['NickName'], 'hp' => 100];
                    $player2 = ['name' => $nickName, 'hp' => 100];
                    text::send($message['from']['UserName'], static::battle($message, $player1, $player2));
                    return true;
                }
            }

            //随机事件
            static::randomEvent($message);

            if ($message['type'] === 'text' && $message['fromType'] === 'Group' && $message['isAt']) {
                text::send($message['from']['UserName'], messageHandler::reply($message['pure'], $message['from']['UserName']));
                return true;
            }
        }
    }

    /**
     * 获取群成员显示名
     * @param $message
     * @param string $nickName
     * @return mixed
     */
    public static function getDisplayName($message, $nickName = '') {
        if ($nickName) {
            return wxBot::getInstance()->groups->getMemberDisplayNameByNickName($message['from']['UserName'], $nickName);
        } else {
            return wxBot::getInstance()->groups->getMemberDisplayName($message['from']['UserName'], $message['sender']['UserName']);
        }
    }

    /**
     * 根据名称获取昵称
     * @param $message
     * @param $name
     * @return string
     */
    public static function getNickName($message, $name) {
        $nickName = '';
        if (wxBot::getInstance()->groups->getMembersByNickname($message['from']['UserName'], $name)) {
            $nickName = $name;
        } elseif ($member = wxBot::getInstance()->groups->getMembersByDisplayname($message['from']['UserName'], $name)) {
            $nickName = $member[0]['NickName'];
        }
        return $nickName;
    }

    /**
     * 获取群成员积分
     * @param $message
     * @param string $nickName
     * @return mixed
     */
    public static function getScore($message, $nickName = '') {
        $nickName || $nickName = $message['sender']['NickName'];
        if (!isset(static::$score[$message['from']['NickName']][$nickName])) {
            static::$score[$message['from']['NickName']][$nickName] = 100; //初始100分
        }
        return static::$score[$message['from']['NickName']][$nickName];
    }


    /**
     * 积分变动
     * @param $message
     * @param $score
     * @param string $nickName
     */
    public static function changeScore($message, $score = 1, $nickName = '') {
        $nickName || $nickName = $message['sender']['NickName'];
        static::$score[$message['from']['NickName']][$nickName] = static::getScore($message, $nickName) + $score;
        static::storeScore();
    }


    /**
     * 检测积分是否足够
     * @param $message
     * @param int $score
     * @param string $nickName
     * @return bool
     */
    public static function checkScore($message, $score = 1, $nickName = '') {
        return self::getScore($message, $nickName) >= $score;
    }

    /**
     * 持久化积分数据
     */
    public static function storeScore() {
        wxBot::getInstance()->saveToFile('score.json', static::$score);
    }

    /**
     * 初始化积分数据
     */
    public static function initScore() {
        if ($score = wxBot::getInstance()->getFromFile('score.json')) {
            static::$score = $score;
        } else {
            static::$score = [];
        }
    }


    /**
     * 获取积分排行
     * @param $userName
     * @return string
     */
    public static function getScoreRank($userName) {
        $msg = '=====\ue12f 积分榜 \ue12f=====' . PHP_EOL;
        if (static::$score[$userName]) {
            $score = static::$score[$userName];
            arsort($score, SORT_NUMERIC);
            $i = 1;
            foreach ($score as $k => $v) {
                $msg .= $i . '、' . $k . ' (' . $v . ')' . PHP_EOL;
                if ($i > 14) {
                    break;
                }
                $i++;
            }
        } else {
            $msg .= '暂时没有积分排行数据！';
        }
        return emojiDecode($msg);
    }

    /**
     * 签到
     * @param $message
     * @return bool
     */
    public static function signIn($message) {
        $today = date('Y-m-d');
        if (isset(static::$signIn[$message['from']['NickName']]) && !isset(static::$signIn[$message['from']['NickName']][$today])) {
            //初始化
            static::$signIn[$message['from']['NickName']] = [];
        }
        if (!isset(static::$signIn[$message['from']['NickName']][$today][$message['sender']['NickName']])) {
            static::changeScore($message, 1);
            static::$signIn[$message['from']['NickName']][$today][$message['sender']['NickName']] = 1;
            static::storeSignIn();
            return true;
        }
        return false;
    }

    /**
     * 持久化签到数据
     */
    public static function storeSignIn() {
        wxBot::getInstance()->saveToFile('signin.json', static::$signIn);
    }

    /**
     * 初始化签到数据
     */
    public static function initSignIn() {
        if ($signIn = wxBot::getInstance()->getFromFile('signin.json')) {
            static::$signIn = $signIn;
        } else {
            static::$signIn = [];
        }
    }


    /**
     * 随机事件
     * @param $message
     */
    public static function randomEvent($message) {
        if (mt_rand(1, 100) <= 3) {
            $event = static::$events[array_rand(static::$events)];
            static::changeScore($message, $event['score']);
            text::send($message['from']['UserName'], '【随机事件】@' . $message['sender']['NickName'] . ' ' . $event['text'] . ' 积分' . ($event['score'] > 0 ? '+' . $event['score'] : $event['score']) . ' 总积分' . static::getScore($message));
        }
    }

    /**
     * PK战斗
     * @param $message
     * @param $player1
     * @param $player2
     * @return string
     */
    public static function battle($message, $player1, $player2) {
        $round = 0;
        $battle = [];
        $msg = '===== 【' . $player1['name'] . '】 VS 【' . $player2['name'] . '】 =====' . PHP_EOL;
        while ($player1['hp'] > 0 && $player2['hp'] > 0) {
            $round++;
            $attack = mt_rand(5, 25);
            $player2['hp'] -= $attack;
            $battle[] = '【' . $player1['name'] . '】 挥刀砍向 【' . $player2['name'] . '】，造成 ' . $attack . ' 点伤害';

            $attack = mt_rand(5, 25);
            $player1['hp'] -= $attack;
            $battle[] = '【' . $player2['name'] . '】 一枪刺向 【' . $player1['name'] . '】，造成 ' . $attack . ' 点伤害';
        }
        $msg .= implode(PHP_EOL, $battle) . PHP_EOL . PHP_EOL;

        $winner = $player2['hp'] > $player1['hp'] ? $player2 : $player1;
        $loser = $player2['hp'] > $player1['hp'] ? $player1 : $player2;

        static::changeScore($message, 5, $winner['name']);
        static::changeScore($message, -5, $loser['name']);

        $msg .= '经过 ' . $round . ' 回合激战，【' . $loser['name'] . '】最终不敌身亡' . PHP_EOL;

        $msg .= $winner['name'] . ' 积分+5，总积分 ' . static::getScore($message, $winner['name']) . PHP_EOL;
        $msg .= $loser['name'] . ' 积分-5，总积分 ' . static::getScore($message, $loser['name']) . PHP_EOL;
        return $msg;
    }
}
