<?php

namespace Gekko\Console\PHP;

use \Gekko\Console\{ ConsoleContext, Command, ProcessSpawner };

class ServerCommand extends Command
{
    use ProcessSpawner;

    public function run(ConsoleContext $ctx) : int
    {
        // argv[1] should be the ServerCommand's name
        if ($ctx->getArgumentsCount() < 3)
        {
            \fwrite(STDOUT, "Usage: gko php-server [ start | stop ]");
            return -1;
        }

        $operation = $ctx->getArguments()[2];
        
        if ($operation === "start")
        {
            $pid = $this->spawn($ctx, "php-server", "php", "-S localhost:8080 index.php");

            return $pid > 0 ? 0 : -1;
        }
        else if ($operation === "stop")
        {
            return $this->kill($ctx, "php-server", "php") ? 0 : -1;
        }

        return -2;
    }
}
