<?php
namespace Ptimer;
/**
 * Created by PhpStorm.
 * User: randy
 * Date: 2016/10/19
 * Time: 17:51
 */
class task
{
    public $id;

    /**
     * @var null 间隔的秒数
     */
    public $interval = null;

    public $command;

    public $is_persistent = true;

    /**
     * 是否暂停task
     * @var bool
     */
    public $stop = false;

    /**
     * @var 定时脚本触发的时间戳
     */
    public $triggerTime;

    /**
     * task constructor.
     * @param $func 任务处理函数
     * @param $time 时间间隔 | 时间戳
     * @param bool $persistent 默认周期执行的timer_task
     */
    public function __construct($command, $time, $persistent = true)
    {
        if (empty($command)) {
            exit('Pls set command !');
        }
        if ($time < 1) {
            exit('the $time must greater than 1 !');
        }
        $this->command = $command;
        if ($persistent) {
            $this->interval = $time;
        } else {
            $this->triggerTime = $time;
            $this->is_persistent = false;
        }
    }

}