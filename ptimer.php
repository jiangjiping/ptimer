<?php

namespace Ptimer;
require_once 'config.php';

class ptimer
{
    public static $timer_tasks = [];
    public static $commands = ['start', 'stop', 'status'];

    public static $shm_id = null;

    public static function start()
    {
        (new self)
            ->envCheck()
            ->registerErrorHandler()
            ->commandParse()
            ->daemonize()
            ->runAsNobody()
            ->initShmop()
            ->installSignalHandler()
            ->beginTick()
            ->showResult()
            ->loop();
    }

    /**
     * @return $this
     */
    public function envCheck()
    {
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            exit("php version must newer than 5.3.0!\n");
        }
        if (!extension_loaded('pcntl')) {
            exit("pls install pcntl extension!\n");
        }
        if (!extension_loaded('posix')) {
            exit('pls install posix extension!' . "\n");
        }
        if (!extension_loaded('shmop')) {
            exit('pls install shmop extension!' . "\n");
        }
        return $this;
    }

    public function registerErrorHandler()
    {
        error_reporting(2047);
        ini_set('display_errors', 1);
        register_shutdown_function(function () {
            $this->log(json_encode(error_get_last()));
            @unlink(pid_file);
            @shmop_delete(self::$shm_id);
        });
        return $this;
    }

    /**
     * @return $this
     */
    public function commandParse()
    {
        global $argv;
        if (!isset($argv[0]) || !isset($argv[1])) {
            exit("usage: php ptimer.php [start|stop|status]\n");
        }
        $pid = is_file(pid_file) ? (int)file_get_contents(pid_file) : 0;
        $cmd = $argv[1];
        in_array($cmd, self::$commands) || exit("the command '{$cmd}' is not support!\n");
        switch ($cmd) {
            case 'start':
                //the ptimer has booted
                if ($pid > 0 && posix_kill($pid, 0)) {
                    exit(0);
                }
                echo 'starting...' . "\n";
                break;
            case 'stop':
                @posix_kill($pid, SIGINT);
                exit(0);
                break;
            case 'status':
                posix_kill($pid, SIGUSR2);
                exit(0);
            default:
                break;
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function beginTick()
    {
        pcntl_alarm(1);
        return $this;
    }

    /**
     * @return $this
     */
    public function showResult()
    {
        global $stdout, $stderr;
        @fclose(STDERR);
        @fclose(STDOUT);
        $stdout = fopen("/dev/null", 'a+');
        $stderr = fopen(log_file, 'a+');
        return $this;
    }

    /**
     * @return $this
     */
    public function daemonize()
    {
        umask(0);
        $pid = pcntl_fork();
        if ($pid < 0) {
            exit('daemonize fork error, pls try again!' . "\n");
        } elseif ($pid > 0) {
            exit(0);
        }
        if (posix_setsid() === -1) {
            exit("daemonize setsid error, pls try again!\n");
        }
        touch(log_file);
        $pid = posix_getpid();
        file_put_contents(pid_file, $pid);
        return $this;
    }

    /**
     * @return $this
     */
    public function runAsNobody()
    {
        $nobody = posix_getpwnam('nobody');
        @posix_setgid($nobody['gid']);
        @posix_setuid($nobody['uid']);
        return $this;
    }

    /**
     * @return $this
     */
    public function initShmop()
    {
        self::$shm_id = shmop_open(shmkey, 'c', 0777, 1024);
        return $this;
    }

    /**
     * @return $this
     */
    public function installSignalHandler()
    {
        //注册闹钟处理函数
        pcntl_signal(SIGALRM, function () {
            pcntl_alarm(1);
            $time_now = time();
            $now = date('Y-m-d H:i:s', $time_now);
            foreach (self::$timer_tasks as $timer_id => &$task) {
                if ($task->triggerTime <= $time_now) {
                    $flag = "php_timer_{$timer_id}";
                    $this->singletonCommand($flag, function () use (&$task, &$flag, &$now) {
                        $cmd = "{$task->command} {$flag} >/dev/null &";
                        $this->log('execute_task: ' . $cmd);
                        system($cmd);
                    });
                    if ($task->is_persistent) {
                        $task->triggerTime += $task->interval;
                    } else {
                        unset(self::$timer_tasks[$timer_id]);
                    }
                }
            }
        });

        //注册共享内存可读处理函数
        pcntl_signal(SIGUSR1, function () {
            $task = trim(shmop_read(self::$shm_id, 0, 1024));
            $task = json_decode($task);
            if (empty($task)) {
                return;
            }
            if (isset($task->command) && $task->command == 'remove') {
                unset(self::$timer_tasks[$task->timer_id]);
            } else {
                self::$timer_tasks[$task->id] = $task;
            }
            $this->formatCrontab();
        });

        //注册status命令处理函数
        pcntl_signal(SIGUSR2, [$this, 'formatCrontab']);

        //注册stop命令处理函数
        pcntl_signal(SIGINT, function () {
            @unlink(pid_file);
            @shmop_delete(self::$shm_id);
            @unlink(crontab_file);
            exit(0);
        });
        return $this;
    }


    public function formatCrontab()
    {
        $all_task = array();
        foreach (self::$timer_tasks as $item) {
            $all_task[] = [
                'id'            => $item->id,
                'interval'      => $item->interval,
                'command'       => $item->command,
                'triggerTime'   => $item->triggerTime > 0 ? date('Y-m-d H:i:s', $item->triggerTime) : 0,
                'is_persistent' => $item->is_persistent
            ];
        }
        $all_task = var_export($all_task, true);
        file_put_contents(crontab_file, "<?php\r\n return " . $all_task . "; \r\n?>");

    }

    /**
     * 一个command只能用一个进程。防止定时脚本还没执行完，但是定时器已经触发多次，
     * 造成同一个php shell command运行N个，影响系统稳定性
     * @param $flag 自定义的command标识
     * @param Closure $func
     */
    public function singletonCommand($flag, \Closure $func)
    {
        //获取命令输出的最后一行
        $last_line = system("ps aux | grep {$flag} | grep -v grep");
        if (empty($last_line)) {
            $func();
        }
    }

    public function log($str)
    {
        return file_put_contents(log_file, '[' . date('Y-m-d H:i:s') . '] ' . $str . "\n", FILE_APPEND);
    }

    public function loop()
    {
        while (1) {
            pcntl_signal_dispatch();
            /**
             * 睡眠时间长短不影响定时器, sleep会自动被进程信号[]唤醒
             * invoke sleep function to prevent cpu high usage.
             */
            sleep(1);
        }
    }

}


ptimer::start();
