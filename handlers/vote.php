<?php
/**
 * 投票
 * @author 罗会铸 yeyupl@qq.com
 */

namespace fw\service\wxBot\handlers;

use fw\service\wxBot\message\text;
use fw\service\wxBot\wxBot;

class vote {

    private static $letter = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];

    public static function messageHandler($message) {
        if ($message['type'] === 'text' && $message['fromType'] === 'Group' && in_array($message['from']['NickName'], wxBot::getInstance()->config['groups'])) {
            $username = $message['from']['UserName'];

            $isBegin = isset(game::$games[$username]);

            if ($isBegin && game::$games[$username]['name'] != '投票') {
                return false;
            }

            $isVote = $isBegin && game::$games[$username]['title'] && game::$games[$username]['type'] && game::$games[$username]['option'];

            if ($message['pure'] === '投票') {
                if ($isBegin) {
                    if (!game::$games[$username]['title']) {
                        text::send($username, '【投票】正在进行中，请@' . game::$games[$username]['owner'] . ' 输入投票标题');
                        return true;
                    }
                    if (!game::$games[$username]['type']) {
                        text::send($username, '【投票】正在进行中，请@' . game::$games[$username]['owner'] . ' 输入投票类型，单选或多选');
                        return true;
                    }
                    if (!game::$games[$username]['option']) {
                        text::send($username, '【投票】正在进行中，请@' . game::$games[$username]['owner'] . ' 输入投票选项，多个选项以/隔开');
                        return true;
                    }
                    text::send($username, static::getVote($username));
                } else {
                    $msg = '【投票】开始，请@' . $message['sender']['NickName'] . ' 出题和负责结束投票，参与投票者积分+1' . PHP_EOL;
                    $msg .= '首先，请@' . $message['sender']['NickName'] . ' 输入标题';
                    text::send($username, $msg);
                    game::$games[$username]['owner'] = $message['sender']['NickName'];
                    game::$games[$username]['name'] = '投票';
                    game::$games[$username]['title'] = '';
                    game::$games[$username]['type'] = '';
                    game::$games[$username]['option'] = [];
                    game::$games[$username]['result'] = [];
                    game::$games[$username]['start_time'] = time();
                }
                return true;
            } elseif ($isBegin && $message['sender']['NickName'] == game::$games[$username]['owner'] && (!game::$games[$username]['title'] || !game::$games[$username]['type'] || !game::$games[$username]['option'])) {
                if (!game::$games[$username]['title']) {
                    game::$games[$username]['title'] = $message['content'];
                    text::send($username, '请@' . $message['sender']['NickName'] . ' 请输入投票类型，单选或多选');
                    return true;
                }
                if (!game::$games[$username]['type']) {
                    if (in_array($message['content'], ['单选', '多选'])) {
                        game::$games[$username]['type'] = $message['content'];
                        text::send($username, '@' . $message['sender']['NickName'] . ' 请输入投票选项，多个选项以/隔开，如：男/女/保密');
                    } else {
                        text::send($username, '@' . $message['sender']['NickName'] . ' 请输入正确的投票类型，单选或多选');
                    }
                    return true;
                }
                if (str_contains($message['content'], '/')) {
                    $array = explode('/', $message['content']);
                    if (count($array) > 10) {
                        text::send($username, '@' . $message['sender']['NickName'] . ' 最多只能10个选项');
                        return true;
                    }
                    $i = 0;
                    foreach ($array as $val) {
                        if ($val) {
                            game::$games[$username]['option'][static::$letter[$i]] = static::$letter[$i] . '.' . $val;
                            $i++;
                        }
                    }
                    text::send($username, static::getVote($username));
                    return true;
                }
                text::send($username, '@' . $message['sender']['NickName'] . ' 投票选项格式错误，应如：男/女/保密');
                return true;
            } elseif ($isVote && preg_match('/^[a-jA-J]{1,10}$/', $message['content'])) {
                if (isset(game::$games[$username]['result'][$message['sender']['UserName']])) {
                    text::send($username, '@' . $message['sender']['NickName'] . ' 你已投过票了！');
                    return true;
                }
                $answer = strtoupper($message['content']);
                if (game::$games[$username]['type'] == '单选' && in_array($answer, array_keys(game::$games[$username]['option']))) {
                    game::$games[$username]['result'][$message['sender']['UserName']] = [$answer];
                    group::changeScore($message, 1);
                    text::send($username, '@' . $message['sender']['NickName'] . ' 你投了 ' . $answer . '，积分+1，总积分 ' . group::getScore($message));
                    return true;
                }
                if (game::$games[$username]['type'] == '多选') {
                    game::$games[$username]['result'][$message['sender']['UserName']] = [];
                    for ($i = 0; $i < strlen($answer); $i++) {
                        if (in_array($answer{$i}, array_keys(game::$games[$username]['option']))) {
                            game::$games[$username]['result'][$message['sender']['UserName']][] = $answer{$i};
                        }
                    }
                    game::$games[$username]['result'][$message['sender']['UserName']] = array_unique(game::$games[$username]['result'][$message['sender']['UserName']]);
                    group::changeScore($message, 1);
                    text::send($username, '@' . $message['sender']['NickName'] . ' 你投了 ' . implode('', game::$games[$username]['result'][$message['sender']['UserName']]) . '，积分+1，总积分 ' . group::getScore($message));
                    return true;
                }
            } elseif ($isVote && $message['content'] == '我的投票') {
                if (!isset(game::$games[$username]['result'][$message['sender']['UserName']])) {
                    text::send($username, '@' . $message['sender']['NickName'] . ' 你还没投过票呢！');
                    return true;
                }
                text::send($username, '@' . $message['sender']['NickName'] . ' 你投了 ' . implode('', game::$games[$username]['result'][$message['sender']['UserName']]));
                return true;
            } elseif ($isVote && (($message['sender']['NickName'] == game::$games[$username]['owner'] && $message['content'] == '结束投票') || time() - game::$games[$username]['start_time'] >= 180)) {
                if (count(game::$games[$username]['result'][$message['sender']['UserName']]) > 0) {
                    $result = [];
                    foreach (game::$games[$username]['result'] as $val) {
                        foreach ($val as $v) {
                            $result[$v]++;
                        }
                    }
                    $msg = '';
                    foreach (game::$games[$username]['option'] as $key => $value) {
                        $msg .= $value . ' ----- ' . intval($result[$key]) . '票' . PHP_EOL;
                    }
                } else {
                    $msg = '当前无人参与本轮【投票】，投票结束！';
                }
                $msg = '【' . game::$games[$username]['type'] . '】' . game::$games[$username]['title'] . PHP_EOL . $msg;
                text::send($username, $msg);
                unset(game::$games[$username]);
                return true;
            } elseif ($isBegin && time() - game::$games[$username]['start_time'] >= 180) {
                text::send($username, '【投票】时间到了，自动结束本次投票！');
                unset(game::$games[$username]);
            }
        }
        return false;
    }

    /**
     * 获得投票内容
     * @param $username
     * @return string
     */
    private static function getVote($username) {
        $msg = '【' . game::$games[$username]['type'] . '】' . game::$games[$username]['title'] . PHP_EOL;
        foreach (game::$games[$username]['option'] as $option) {
            $msg .= $option . PHP_EOL;
        }
        $msg .= '请输入选项前字母，参与投票，每人只可参与一次！';
        return $msg;
    }
}
