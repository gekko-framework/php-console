<?php

namespace Gekko\Console\PHP;

use \Gekko\Console\{ ConsoleContext, Command};

abstract class PHPCommand extends Command
{
    /**
     * Console context
     *
     * @var ConsoleContext
     */
    private $ctx;

    protected function __construct(ConsoleContext $ctx)
    {
        $this->ctx = $ctx;
    }

    protected function isWindowsOs() : bool
    {
        return strcasecmp(substr(PHP_OS, 0, 3), 'WIN') == 0;
    }

    protected function startProcess(string $name, string $command) : int
    {
        if ($this->isWindowsOs())
        {
            return $this->startWindowsProcess($name, $command);
        }
        else
        {
            return -1;
        }
    }

    protected function stopProcess(string $name) : bool
    {
        if ($this->isWindowsOs())
        {
            return $this->stopWindowsProcess($name);
        }
        else 
        {
            return false;
        }
    }

    private function startWindowsProcess(string $name, string $command) : int
    {
        $uid = $name . "-" . sha1($this->ctx->getRootDirectory());

        $tmp_dir = $this->ctx->toLocalPath("/.tmp/php");

        if (!\file_exists($tmp_dir))
            mkdir($tmp_dir, 0777, true);

        $pid_file = "{$tmp_dir}/{$uid}.pid";

        if (\file_exists($pid_file))
        {
            \fwrite(STDOUT, "PHP server is already running (PID " . \file_get_contents($pid_file) .  ")");
            return -1;
        }

        pclose(popen("start \"{$uid}\" /MIN $command", "r"));
        $fd = popen("tasklist /fi \"WindowTitle eq {$uid}\" /fo CSV /nh", "r");
        $output = stream_get_contents($fd);
        pclose($fd);

        $current_pids = [];
        $lines = explode("\n", $output);

        foreach ($lines as $line)
        {
            $columns = explode("\",\"", $line);
            
            if (isset($columns[1]))
            {
                $current_pids[] = $columns[1];
            }
        }

        if (count($current_pids) != 1)
            return -1;

        $pid = \intval(array_pop($current_pids));

        if ($pid <= 0)
            return -1;

        \file_put_contents($pid_file, $pid);

        return $pid;
    }

    private function stopWindowsProcess(string $name) : bool
    {
        $uid = $name . "-" . sha1($this->ctx->getRootDirectory());
        $pid_file = $this->ctx->toLocalPath("/.tmp/php/{$uid}.pid");

        if (!\file_exists($pid_file))
            return false;

        $pid = \file_get_contents($pid_file);

        if (empty($pid) || !\is_numeric($pid))
            return false;

        pclose(popen("taskkill /F /pid {$pid}", "r"));

        \unlink($pid_file);

        return true;
    }
}