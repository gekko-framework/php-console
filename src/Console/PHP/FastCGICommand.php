<?php

namespace Gekko\Console\PHP;

use \Gekko\Console\{ ConsoleContext, Command, ProcessSpawner};

class FastCGICommand extends Command
{
    use ProcessSpawner;

    public function run(ConsoleContext $ctx) : int
    {
        // argv[1] should be the FastCGICommand's name
        if ($ctx->getArgumentsCount() < 3)
        {
            \fwrite(STDOUT, "Usage: gko php-cgi [ start | stop ]");
            return -1;
        }

        $operation = $ctx->getArguments()[2];
        
        if ($operation === "start")
        {
            $pid = $this->start($ctx);

            return $pid > 0 ? 0 : -1;
        }
        else if ($operation === "stop")
        {
            return $this->stop($ctx) ? 0 : -1;
        }

        return -2;
    }

    public function start(ConsoleContext $ctx) : int
    {
        return $this->spawn($ctx, "php-cgi", "php-cgi", "-b 127.0.0.1:9123");
    }

    public function stop(ConsoleContext $ctx) : bool
    {
        return $this->kill($ctx, "php-cgi");
    }
}
