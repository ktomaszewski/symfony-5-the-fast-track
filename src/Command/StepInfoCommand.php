<?php

declare(strict_types=1);

namespace App\Command;

use Psr\Cache\CacheItemInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Contracts\Cache\CacheInterface;

class StepInfoCommand extends Command
{
    protected static $defaultName = 'app:step:info';

    /** @var CacheInterface */
    private $cache;

    public function __construct(CacheInterface $cache)
    {
        parent::__construct();

        $this->cache = $cache;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $step = $this->cache->get('app.current_Step', static function (CacheItemInterface $item) {
            $process = new Process(['git', 'tag', '-l', '--points-at', 'HEAD']);
            $process->mustRun();
            $item->expiresAfter(30);

            return $process->getOutput();
        });
        $output->write($step);

        return 0;
    }
}

