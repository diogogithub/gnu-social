<?php
/*
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2008, 2009, StatusNet, Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Recursively deletes a directory tree.
 *
 * @param string $folder The directory path.
 * @param bool $keepRootFolder Whether to keep the top-level folder.
 * @return bool TRUE on success, otherwise FALSE.
 */
function deleteTree(
    $folder,
    $keepRootFolder = false
) {
    // Handle bad arguments.
    if (empty($folder) || !file_exists($folder)) {
        return true; // No such file/folder exists.
    } elseif (is_file($folder) || is_link($folder)) {
        return @unlink($folder); // Delete file/link.
    }

    // Delete all children.
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $fileinfo) {
        $action = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        if (!@$action($fileinfo->getRealPath())) {
            return false; // Abort due to the failure.
        }
    }

    // Delete the root folder itself?
    return (!$keepRootFolder ? @rmdir($folder) : true);
}
