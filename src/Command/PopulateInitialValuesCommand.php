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
 * Command to load the needed initial database values (like the language list)
 *
 * @package  GNUsocial
 * @category Command
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Command;

use App\Core\DB\DB;
use App\Entity\Language;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Intl\Locales;

/**
 * Get a list of event registered in GNU social
 *
 * Testing unfeasable, since it outputs stuff
 *
 * @codeCoverageIgnore
 */
class PopulateInitialValuesCommand extends Command
{
    protected static $defaultName = 'app:populate_initial_values';

    protected function configure()
    {
        $this->setDefinition([])
            ->setDescription('Load the initial table values for tables like `language`')
            ->setHelp(
                <<<'EOF'
                    The <info>%command.name%</info> command loads the initial table values for tables:

                      <info>php %command.full_name%</info>

                    Currently, this loads the initial values for the `language` table
                    EOF,
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (DB::count('language', []) !== 0) {
            $output->writeln('<error>The `language` table already has values, aborting</error>');
            return 1;
        }

        foreach (Locales::getNames() as $key => $name) {
            DB::persist(Language::create(['locale' => $key, 'short_display' => $key, 'long_display' => $name]));
        }
        DB::flush();
        $output->writeln('<info>Populated the `language` table</info>');
        return 0;
    }
}
