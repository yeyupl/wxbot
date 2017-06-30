<?php
/**
 * 调查
 * @author 罗会铸 yeyupl@qq.com
 */

namespace fw\service\wxBot\handlers;

use fw\service\wxBot\message\text;
use fw\service\wxBot\wxBot;

class survey {

    private static $letter = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];

    public static function messageHandler($message) {
        if ($message['type'] === 'text' && $message['fromType'] === 'Group' && in_array($message['from']['NickName'], wxBot::getInstance()->config['groups'])) {
            $username = $message['from']['UserName'];

            $isBegin = isset(game::$games[$username]);

            if ($isBegin && game::$games[$username]['name'] != '调查') {
                return false;
            }

            $isVote = $isBegin && game::$games[$username]['title'] && game::$games[$username]['type'] && game::$games[$username]['option'];

            if ($message['pure'] === '调查') {
                if ($isBegin) {
                    if (game::$games[$username]['status'] == 0) {
                        text::send($username, '@' . game::$games[$username]['owner'] . ' 正在制作调查问卷，请耐心等候！');
                        return true;
                    }
                    if (game::$games[$username]['status'] == 1) {
                        text::send($username, '@' . $message['sender']['NickName'] . ' 调查正在进行中，请积极参与！');
                        return true;
                    }
                    text::send($username, static::getSurvey($username));
                } else {
                    $msg = '【调查】开始，请@' . $message['sender']['NickName'] . ' 制作问卷和负责结束调查，参与调查者积分+3' . PHP_EOL;
                    $msg .= '首先，请@' . $message['sender']['NickName'] . ' 输入问卷标题' . PHP_EOL;
                    text::send($username, $msg);
                    game::$games[$username]['owner'] = $message['sender']['NickName'];
                    game::$games[$username]['name'] = '调查';
                    game::$games[$username]['title'] = '';
                    game::$games[$username]['items'] = [];
                    game::$games[$username]['result'] = [];
                    game::$games[$username]['start_time'] = time();
                    game::$games[$username]['status'] = 0;
                }
                return true;
            } elseif ($isBegin && $message['sender']['NickName'] == game::$games[$username]['owner'] && game::$games[$username]['status'] == 0) {
                if (!game::$games[$username]['title']) {
                    game::$games[$username]['title'] = $message['content'];
                    $msg = '请@' . $message['sender']['NickName'] . ' 开始制作问卷内容，规则如下：' . PHP_EOL;
                    $msg .= '1.每次输入制作一道题' . PHP_EOL;
                    $msg .= '2.输入 完成问卷 完成制作，开始进入调查' . PHP_EOL;
                    $msg .= '3.单选题：单选|这是单选标题|选项一/选项二/选项三' . PHP_EOL;
                    $msg .= '4.多选题：多选|这是多选标题|选项一/选项二/选项三' . PHP_EOL;
                    $msg .= '5.问答题：问答|这是问答标题' . PHP_EOL;
                    text::send($username, $msg);
                    return true;
                }
                if ($message['content'] == '预览问卷') {
                    text::send($username, static::getSurvey($username));
                    return true;
                }
                if ($message['content'] == '放弃问卷') {
                    text::send($username, '@' . $message['sender']['NickName'] . ' 你已放弃本次调查');
                    unset(game::$games[$username]);
                    return true;
                }
                if ($message['content'] == '完成问卷') {
                    if (!game::$games[$username]['items']) {
                        text::send($username, '@' . $message['sender']['NickName'] . ' 你还没有制作问卷内容，请继续完善内容，如要放弃请输入 放弃问卷');
                        return true;
                    }
                    game::$games[$username]['status'] = 1;
                    text::send($username, static::getSurvey($username, true));
                    return true;
                }
                if (str_contains($message['content'], '|')) {
                    $array = explode('|', $message['content']);
                    $type = trim($array[0]);
                    $title = trim($array[1]);
                    if ($type && $title) {
                        $items = ['title' => $title, 'type' => $type];
                        if (in_array($type, ['单选', '多选']) && $array[2] && str_contains($array[2], '/')) {
                            $options = explode('/', trim($array[2]));
                            if (count($options) > 10) {
                                text::send($username, '@' . $message['sender']['NickName'] . ' 选择题最多只能10个选项');
                                return true;
                            }
                            $i = 0;
                            foreach ($options as $option) {
                                if ($option) {
                                    $items['option'][static::$letter[$i]] = static::$letter[$i] . '.' . $option;
                                    $i++;
                                }
                            }
                        }
                        game::$games[$username]['items'][] = $items;
                        text::send($username, '@' . $message['sender']['NickName'] . ' 请继续制作下一题，或 完成问卷');
                        return true;
                    }
                }
                text::send($username, '@' . $message['sender']['NickName'] . ' 问卷题目格式错误，请参照刚才的规则制作问卷');
                return true;
            } elseif ($isBegin && game::$games[$username]['status'] == 1) {
                if ($message['content'] == '查看问卷') {
                    text::send($username, static::getSurvey($username));
                    return true;
                }
                if (($message['sender']['NickName'] == game::$games[$username]['owner'] && $message['content'] == '结束调查') || time() - game::$games[$username]['start_time'] >= 600) {
                    text::send($username, '调查结果');
                    unset(game::$games[$username]);
                    return true;
                }
                if (str_contains($message['content'], '|')) {
                    text::send($username, '参与调查功能正在开发中...！');
                    unset(game::$games[$username]);
                    return true;
                }
            } elseif ($isBegin && time() - game::$games[$username]['start_time'] >= 600) {
                text::send($username, '【调查】时间到了，自动结束本次调查！');
                unset(game::$games[$username]);
            }
        }
        return false;
    }

    /**
     * 获得问卷内容
     * @param $username
     * @param bool $status
     * @return string
     */
    private static function getSurvey($username, $status = false) {
        $msg = '【调查】' . game::$games[$username]['title'] . PHP_EOL . PHP_EOL;
        foreach (game::$games[$username]['items'] as $key => $item) {
            $msg .= ($key + 1) . '、' . $item['title'] . ' (' . $item['type'] . '题)' . PHP_EOL;
            if (in_array($item['type'], ['单选', '多选']) && $item['option']) {
                foreach ($item['option'] as $option) {
                    $msg .= $option . PHP_EOL;
                }
            }
            $msg .= PHP_EOL;
        }
        if ($status) {
            $msg .= '调查开始了，按题目顺序，回答用|分隔' . PHP_EOL;
            $msg .= '例如:这是问答内容|A|ABC' . PHP_EOL;
        }
        return $msg;
    }
}
