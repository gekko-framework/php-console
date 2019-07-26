<?php

namespace Gekko\Console\PHP;

use \Gekko\Console\{ ConsoleContext, Command};

class ServerCommand extends Command
{
    public function run(ConsoleContext $ctx) : int
    {
        // argv[1] should be the ServerCommand's name
        if ($ctx->getArgumentsCount() < 3)
        {
            \fwrite(STDOUT, "Usage: gko php-server [ start | stop ]");
            return -1;
        }

        $operation = $ctx->getArguments()[2];
        
        $is_windows = strcasecmp(substr(PHP_OS, 0, 3), 'WIN') == 0;

        if ($operation === "start")
        {
            if ($is_windows) {
                $this->startWindows($ctx);
            } 

            return 0;
        }
        else if ($operation === "stop")
        {
            if ($is_windows) {
                $this->stopWindows($ctx);
            }
        }

        return -2;
    }

    private function startWindows(ConsoleContext $ctx) : int
    {
        $dir = $ctx->toLocalPath("/.tmp/php");

        if (!\file_exists($dir))
            mkdir($dir);

        $pid_file = "{$dir}/.php-server.pid";

        if (\file_exists($pid_file))
        {
            \fwrite(STDOUT, "PHP server is already running (PID " . \file_get_contents($pid_file) .  ")");
            return -1;
        }

        $previous_pids = [];
        $fd = popen("tasklist /fi \"imagename eq php.exe\" /fo CSV /nh", "r");
        $output = stream_get_contents($fd);
        pclose($fd);

        $lines = explode("\n", $output);

        foreach ($lines as $line)
        {
            $columns = explode("\",\"", $line);
            
            if (isset($columns[1]))
            {
                $previous_pids[] = $columns[1];
            }
        }

        pclose(popen("start /B php -S localhost:8080 index.php", "r"));

        $current_pids = [];
        $fd = popen("tasklist /fi \"imagename eq php.exe\" /fo CSV /nh", "r");
        $output = stream_get_contents($fd);
        pclose($fd);

        $lines = explode("\n", $output);

        foreach ($lines as $line)
        {
            $columns = explode("\",\"", $line);
            
            if (isset($columns[1]))
            {
                $current_pids[] = $columns[1];
            }
        }

        $pid = \array_diff($current_pids, $previous_pids);

        if (empty($pid))
            return -2;

        $pid = \array_pop($pid);

        \file_put_contents($pid_file, \intval($pid));

        return 0;
    }

    private function stopWindows(ConsoleContext $ctx) : int
    {
        $pid_file = $ctx->toLocalPath("/.tmp/php/.php-server.pid");
        if (!\file_exists($pid_file))
            return -1;

        $pid = \file_get_contents($pid_file);

        if (empty($pid) || !\is_numeric($pid))
            return -2;

        pclose(popen("taskkill /F /pid {$pid}", "r"));

        \unlink($pid_file);

        return 0;
    }
}
