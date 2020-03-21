<?php

/*
 * This file is part of GNU social - https://www.gnu.org/software/social
 *
 * GNU social is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * GNU social is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with GNU social.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Command to search for event by pattern
 *
 * @package  GNUsocial
 * @category Command
 *
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

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
