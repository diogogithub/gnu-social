<?php

declare(strict_types = 1);

// {{{ License
// This file is part of GNU social - https://www.gnu.org/software/social
//
// GNU social is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// GNU social is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with GNU social.  If not, see <http://www.gnu.org/licenses/>.
// }}}

/**
 * Command to search for event by pattern
 *
 * @package  GNUsocial
 * @category Command
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Command;

use Functional as F;
use ReflectionFunction;
use Symfony\Bundle\FrameworkBundle\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Get a list of event registered in GNU social
 *
 * Testing unfeasable, since it outputs stuff
 *
 * @codeCoverageIgnore
 */
class ListEventsCommand extends Command
{
    protected static $defaultName = 'app:events';
    private EventDispatcherInterface $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        parent::__construct();
        $this->dispatcher = $dispatcher;
    }

    protected function configure()
    {
        $this->setDefinition([new InputArgument('pattern', InputArgument::OPTIONAL, 'An event pattern to look for')])
            ->setDescription('Search for an event')
            ->setHelp(
                <<<'EOF'
                    The <info>%command.name%</info> command displays GNU social event listeners:

                      <info>php %command.full_name%</info>

                    To get specific listeners for an event, specify its name:

                      <info>php %command.full_name% kernel.request</info>
                    EOF,
            );
    }

    /**
     * Execute the command, outputing a list of GNU social events
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $patterm   = $input->getArgument('pattern') ?? 'GNUsocial.*';
        $listeners = $this->dispatcher->getListeners();
        $listeners = F\select(
            $listeners,
            fn ($_, $key, $__) => preg_match('/' . $patterm . '/', $key),
        );

        ksort($listeners);
        foreach ($listeners as $event => $listener) {
            $event = explode('.', $event)[1];
            $output->writeln("<info>Event '<options=bold>{$event}</options=bold>':</info>");
            foreach ($listener as $c) {
                $r = new ReflectionFunction($c);
                $m = $r->getStaticVariables()['handler'];
                $output->writeln("\t" . \get_class($m[0]) . '::' . $m[1]);
            }
            $output->writeln('');
        }

        if (!$input->hasArgument('pattern')) {
            $io                = new SymfonyStyle($input, $output);
            $options           = [];
            $helper            = new DescriptorHelper();
            $options['output'] = $io;
            $helper->describe($io, null, $options);
        }
        return 0;
    }
}
