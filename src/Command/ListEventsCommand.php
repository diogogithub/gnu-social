<?php

namespace App\Command;

use Functional as F;
use Symfony\Bundle\FrameworkBundle\Command\EventDispatcherDebugCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ListEventsCommand extends EventDispatcherDebugCommand
{
    protected static $defaultName = 'app:events';
    private EventDispatcherInterface $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        parent::__construct($dispatcher);
        $this->dispatcher = $dispatcher;
    }

    protected function configure()
    {
        $this->setDefinition([new InputArgument('pattern',
                                                InputArgument::OPTIONAL,
                                                'An event pattern to look for')])
             ->setDescription('Search for an event');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $options   = [];
        $patterm   = $input->getArgument('pattern') ?? 'GNUsocial.*';
        $listeners = $this->dispatcher->getListeners();
        $listeners = F\select($listeners,
                              function ($_, $key, $__) use ($patterm) {
                                  return preg_match('/' . $patterm . '/', $key);
                              });

        foreach ($listeners as $event => $listener) {
            echo 'Event \'' . $event . "\\' handled by:\n";
            foreach ($listener as $c) {
                $r = new \ReflectionFunction($c);
                echo '    ' . get_class($r->getStaticVariables()['handler'][0]) . "\n";
            }
        }
        return 0;
    }
}
