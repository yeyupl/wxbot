<?php
/**
 * 幸运28
 * @author 罗会铸 yeyupl@qq.com
 */

namespace fw\service\wxBot\handlers;

use fw\service\wxBot\message\text;
use fw\service\wxBot\wxBot;

class guessLuck28 {

    public static function messageHandler($message) {
        if ($message['type'] === 'text' && $message['fromType'] === 'Group' && in_array($message['from']['NickName'], wxBot::getInstance()->config['groups'])) {
            $username = $message['from']['UserName'];

            $isBegin = isset(game::$games[$username]);

            if ($isBegin && game::$games[$username]['name'] != '幸运28') {
                return false;
            }

            if ($isBegin) {
                $message['content'] = str_replace(['-', '～', '－'], '~', $message['content']);
            }
            if ($message['pure'] === '幸运28') {
                if ($isBegin) {
                    text::send($username, '游戏【幸运28】正在进行中，赶紧下注吧！');
                } else {
                    $msg = '【幸运28】开始，每注1分，可以下多注，由@' . $message['sender']['NickName'] . ' 负责开奖。' . PHP_EOL;
                    $msg .= '下注规则：' . PHP_EOL;
                    $msg .= '1.单个下注 0到27的数字，如 9' . PHP_EOL;
                    $msg .= '2.批量下注 如 全包、包双、包单' . PHP_EOL;
                    $msg .= '3.区间下注 如 5~12' . PHP_EOL;
                    $msg .= '4.下注倍数 默认1，可在上述规则后*倍数 如 5~12*3';
                    text::send($username, $msg);
                    game::$games[$username]['owner'] = $message['sender']['NickName'];
                    game::$games[$username]['name'] = '幸运28';
                    game::$games[$username]['data'] = [mt_rand(0, 9), mt_rand(0, 9), mt_rand(0, 9)];
                    game::$games[$username]['start_time'] = time();
                }
                return true;
            } elseif ($isBegin && (is_numeric($message['content']) && in_array($message['content'], range(0, 27)) || in_array($message['content'], ['全包', '包单', '包双']) || str_contains($message['content'], '~') || str_contains($message['content'], '*'))) {
                $range = range(0, 27);
                $count = 0;
                $times = 1;
                if (str_contains($message['content'], '*')) {
                    $array = explode('*', $message['content']);
                    $message['content'] = trim($array[0]);
                    $times = max(intval($array[1]), 1);
                }
                if (is_numeric($message['content']) && in_array($message['content'], $range)) {
                    if (!group::checkScore($message, $times)) {
                        text::send($username, '@' . $message['sender']['NickName'] . ' 积分不足，无法下注！');
                        return true;
                    }
                    game::$games[$username]['result'][$message['sender']['UserName']]['stake'][$message['content']] += $times;
                    game::$games[$username]['result'][$message['sender']['UserName']]['nickName'] = $message['sender']['NickName'];
                    $count = $times;
                } elseif (in_array($message['content'], ['全包', '包单', '包双'])) {
                    if (!group::checkScore($message, ($message['content'] == '全包' ? 28 : 14) * $times)) {
                        text::send($username, '@' . $message['sender']['NickName'] . ' 积分不足，无法下注！');
                        return true;
                    }
                    foreach ($range as $val) {
                        if ($message['content'] == '全包' || ($message['content'] == '包单' && $val % 2 == 1) || ($message['content'] == '包双' && $val % 2 == 0)) {
                            game::$games[$username]['result'][$message['sender']['UserName']]['stake'][$val] += $times;
                            game::$games[$username]['result'][$message['sender']['UserName']]['nickName'] = $message['sender']['NickName'];
                            $count += $times;
                        }
                    }
                } elseif (str_contains($message['content'], '~')) {
                    $array = explode('~', $message['content']);
                    if (count($array) == 2 && is_numeric($array[0]) && is_numeric($array[1]) && in_array($array[0], $range) && in_array($array[1], $range) && $array[0] < $array[1]) {
                        if (!group::checkScore($message, ($array[1] - $array[0] + 1) * $times)) {
                            text::send($username, '@' . $message['sender']['NickName'] . ' 积分不足，无法下注！');
                            return true;
                        }
                        foreach (range($array[0], $array[1]) as $val) {
                            game::$games[$username]['result'][$message['sender']['UserName']]['stake'][$val] += $times;
                            game::$games[$username]['result'][$message['sender']['UserName']]['nickName'] = $message['sender']['NickName'];
                            $count += $times;
                        }
                    }
                }
                group::changeScore($message, $count * -1);
                text::send($username, '@' . $message['sender']['NickName'] . ' 本次下 ' . $count . ' 注，共已下 ' . intval(array_sum(game::$games[$username]['result'][$message['sender']['UserName']]['stake'])) . ' 注');
                return true;
            } elseif ($message['content'] == '我的下注' && $isBegin) {
                if (!isset(game::$games[$username]['result'][$message['sender']['UserName']]['stake'])) {
                    text::send($username, '@' . $message['sender']['NickName'] . ' 你还没下过注呢！');
                } else {
                    $msg = '@' . $message['sender']['NickName'] . ' 你共下 ' . array_sum(game::$games[$username]['result'][$message['sender']['UserName']]['stake']) . ' 注，详情如下：' . PHP_EOL;
                    $result = array();
                    ksort(game::$games[$username]['result'][$message['sender']['UserName']]['stake']);
                    foreach (game::$games[$username]['result'][$message['sender']['UserName']]['stake'] as $k => $v) {
                        $result[] = $k . ': x' . $v;
                    }
                    text::send($username, $msg . implode(PHP_EOL, $result));
                    return true;
                }
                return true;
            } elseif ($message['content'] == '下注情况' && $isBegin) {
                if (!isset(game::$games[$username]['result'])) {
                    text::send($username, '@' . $message['sender']['NickName'] . ' 还没人下过注呢！');
                } else {
                    $range = [];
                    foreach (game::$games[$username]['result'] as $val) {
                        foreach ($val['stake'] as $k => $v) {
                            $range[$k] += $v;
                        }
                    }
                    $sum = array_sum($range);
                    $msg = '截止目前共下 ' . $sum . ' 注，详情如下：' . PHP_EOL;
                    $result = array();
                    ksort($range);
                    foreach ($range as $k => $v) {
                        $result[] = $k . ': x' . $v . '  赔率:' . round($sum / $v);
                    }
                    text::send($username, $msg . implode(PHP_EOL, $result));
                    return true;
                }
                return true;
            } elseif ($isBegin && (($message['sender']['NickName'] == game::$games[$username]['owner'] && $message['content'] == '开奖') || time() - game::$games[$username]['start_time'] >= 180)) {
                if (count(game::$games[$username]['result']) == 0) {
                    Text::send($username, '当前无人参与本轮【幸运28】游戏，游戏结束！');
                    unset(game::$games[$username]);
                    return true;
                }
                $data = game::$games[$username]['data'];

                $target = array_sum($data);
                //计算赔率
                $targetCount = 0;
                $sumCount = 0;
                foreach (game::$games[$username]['result'] as $val) {
                    foreach ($val['stake'] as $k => $v) {
                        $sumCount += $v;
                        if ($k == $target) {
                            $targetCount += $v;
                        }
                    }
                }
                $percent = $targetCount == 0 ? $sumCount : round($sumCount / $targetCount);
                $msg = '开奖结果：' . $data[0] . '+' . $data[1] . '+' . $data[2] . '=' . $target . '，赔率' . $percent . '，共下 ' . $sumCount . ' 注，中 ' . $targetCount . ' 注' . PHP_EOL;
                if ($targetCount == 0) {
                    $msg .= '非常遗憾，无人中奖！' . PHP_EOL;
                }
                $result = array();
                foreach (game::$games[$username]['result'] as $sender => $val) {
                    $sub = 0;
                    $add = 0;
                    foreach ($val['stake'] as $k => $v) {
                        $sub += $v;
                        if ($k == $target) {
                            $add += $v;
                        }
                    }
                    $score = $sub * -1 + $add * $percent;

                    group::changeScore($message, $add * $percent, $val['nickName']);

                    $resMsg = $val['nickName'] . ' 共下 ' . $sub . ' 注，';
                    $resMsg .= $add > 0 ? ('中 ' . $add . ' 注 ，') : '没有中奖，';

                    if ($score > 0) {
                        $resMsg .= '盈利 +' . $score . ' 积分';
                    } elseif ($score == 0) {
                        $resMsg .= '本局盈亏平衡';
                    } else {
                        $resMsg .= '亏损 ' . $score . ' 积分';
                    }
                    $resMsg .= '，总积分 ' . group::getScore($message, $val['nickName']);
                    $result[] = $resMsg;
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
