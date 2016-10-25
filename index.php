<?php
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2016/10/19
 * Time: 17:55
 */

ini_set('display_errors', 1);
error_reporting(2047);

require_once 'ptimerClient.php';
require_once 'task.php';
if (isset($_POST['key'])) {
    $dataset = array();
    $key = $_POST['key'];
    switch ($key) {
        case 'save_get':
            $dataset = \Ptimer\ptimerClient::getCrontabList();
            break;
        case 'remove_get':
            $timer_id = $_POST['timer_id'];
            empty($timer_id) || \Ptimer\ptimerClient::remove($timer_id);
            $dataset = \Ptimer\ptimerClient::getCrontabList();
            break;
        case 'add_get':
            $command = $_POST['command'];
            if (empty($command)) {
                exit(json_encode([
                    'code' => 1,
                    'msg'  => '请设置command！'
                ]));
            }
            $interval = (int)$_POST['interval'];
            $triggerTime = empty($_POST['trigger_time']) ? 0 : strtotime($_POST['trigger_time']);
            $is_persistent = $interval >= 1;
            if ($is_persistent) {
                $interval <= 0 && $interval = 10;
                $time = $interval;
            } else {
                $time = $triggerTime <= 0 ? time() : $triggerTime;
            }
            \Ptimer\ptimerClient::add(new \Ptimer\task($command, $time, $is_persistent));
            $dataset = \Ptimer\ptimerClient::getCrontabList();
        default:
            break;
    }
    if (is_array($dataset) && !empty($dataset)) {
        exit(json_encode([
            'code'  => 0,
            'items' => $dataset
        ]));
    } else {
        exit(json_encode([
            'code'  => 0,
            'items' => null
        ]));
    }

}


include 'view_example.php';