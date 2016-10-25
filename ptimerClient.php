<?php

namespace Ptimer;
require_once 'config.php';
require_once 'task.php';

class ptimerClient
{

    /**
     * 将数据写入共享内存
     * @param $task
     */
    public static function writeToshmop($task)
    {
        $task_data = json_encode($task);
        $shmid = shmop_open(shmkey, 'c', 0777, 1024);
        $task_data = str_pad($task_data, 1024, "\0");
        $flag = shmop_write($shmid, $task_data, 0);
        @shmop_close($shmid);
        if ($flag) {
            $pid = (int)@file_get_contents(pid_file);
            $pid > 0 && @posix_kill($pid, SIGUSR1);
        }
    }

    public static function log($str)
    {
        return file_put_contents(log_file, '[' . date('Y-m-d H:i:s') . '] Ptimer log: ' . $str . "\n", FILE_APPEND);
    }

    public static function add(task $task)
    {
        $task->id = spl_object_hash($task);
        if ($task->is_persistent) {
            $task->triggerTime = time() + $task->interval;
        }
        self::writeToshmop($task);
    }

    public static function remove($timer_id)
    {
        $task = new \stdClass();
        $task->timer_id = $timer_id;
        $task->command = 'remove';
        self::writeToshmop($task);
    }

    public static function getCrontabList()
    {
        //让ptimer进程由足够的时间更新crontab_file
        usleep(10000);
        return is_file(crontab_file) ? (require crontab_file) : false;

    }
}