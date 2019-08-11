<?php
/**
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2011, StatusNet, Inc.
 *
 * URL mapper
 *
 * PHP version 5
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
 *
 * @category  URL
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}

/**
 * URL mapper
 *
 * Converts a path into a set of parameters, and vice versa
 *
 * We used to use Net_URL_Mapper, so there's a wrapper class at Router, q.v.
 *
 * NUM's vagaries are the main reason we have weirdnesses here.
 *
 * @category  URL
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */

class URLMapper
{
    const ACTION = 'action';

    protected $statics = [];
    protected $variables = [];
    protected $reverse_dynamics = [];
    protected $reverse_statics = [];
    protected $allpaths = [];

    /**
     * Route creation.
     * $acceptHeaders should be set to true when, for whatever reason,
     * a path is being re-connected. The $headers list is still optional,
     * in this case, given that being empty means "accept everything".
     *
     * @author Evan Prodromou <evan@status.net>
     * @author Bruno Casteleiro <brunoccast@fc.up.pt>
     * @param  string $path route path
     * @param  array $args route action and, if needed, action settings
     * @param  array $paramPatterns regex patterns for path's parameters
     * @param  bool $acceptHeaders whether a path is being re-connected
     * @param  array $headers headers that should be set for route creation
     * @throws Exception If can't connect
     * @return void
     */
    public function connect(string $path, array $args, array $paramPatterns = [], bool $acceptHeaders = false, array $headers = [])
    {
        if (!array_key_exists(self::ACTION, $args)) {
            throw new Exception(sprintf("Can't connect %s; path has no action.", $path));
        }

        $should = true;
        if ($acceptHeaders) {
            // even if it shouldn't be used as a route, we still want
            // to store some information to allow common_local_url
            // to generate urls
            $should = empty($headers) || self::should($headers);
        }

        $this->allpaths[] = $path;

        $action = $args[self::ACTION];

        $paramNames = $this->getParamNames($path);

        if (empty($paramNames)) {
            $this->statics[$path] = $args;
            if (array_key_exists($action, $this->reverse_statics)) {
                $this->reverse_statics[$action][] = [$args, $path];
            } else {
                $this->reverse_statics[$action] = [[$args, $path]];
            }
        } else {
            // fix for the code that still make improper use of this function's params
            foreach ($paramNames as $name) {
                if (!array_key_exists($name, $paramPatterns) &&
                    array_key_exists($name, $args)) {
                    $paramPatterns[$name] = $args[$name];
                    unset($args[$name]);
                }
            }

            // $variables is used for path matching, so we can't store invalid routes
            if ($should) {
                $regex = self::makeRegex($path, $paramPatterns);
                if (isset($this->variables[$regex]) || !$acceptHeaders) {
                    $this->variables[$regex] = [$args, $paramNames];
                } else {
                    // URLs that differ only in the attribute names will generate
                    // different regexes, so in order to avoid the wrong one (oldest)
                    // to be matched first, fresh regexes are stored at the front
                    $this->variables = [$regex => [$args, $paramNames]] + $this->variables;
                }
            }

            $format = $this->makeFormat($path);

            if (array_key_exists($action, $this->reverse_dynamics)) {
                $this->reverse_dynamics[$action][] = [$args, $format, $paramNames];
            } else {
                $this->reverse_dynamics[$action] = [[$args, $format, $paramNames]];
            }
        }
    }

    public function match($path)
    {
        if (array_key_exists($path, $this->statics)) {
            return $this->statics[$path];
        }

        foreach ($this->variables as $regex => $pattern) {
            list($args, $paramNames) = $pattern;
            if (preg_match($regex, $path, $match)) {
                $results = $args;
                foreach ($paramNames as $name) {
                    $results[$name] = $match[$name];
                }
                return $results;
            }
        }

        throw new NoRouteMapException($path);
    }

    public function generate($args, $qstring, $fragment)
    {
        if (!array_key_exists(self::ACTION, $args)) {
            throw new Exception("Every path needs an action.");
        }

        $action = $args[self::ACTION];

        if (!array_key_exists($action, $this->reverse_dynamics) && !array_key_exists($action, $this->reverse_statics)) {
            throw new Exception(sprintf('No candidate paths for action "%s"', $action));
        }

        $candidates = $this->reverse_dynamics[$action];

        foreach ($candidates as $candidate) {
            list($tryArgs, $format, $paramNames) = $candidate;

            foreach ($tryArgs as $key => $value) {
                if (!array_key_exists($key, $args) || $args[$key] != $value) {
                    // next candidate
                    continue 2;
                }
            }

            // success
            $toFormat = [];

            foreach ($paramNames as $name) {
                if (!array_key_exists($name, $args)) {
                    // next candidate
                    continue 2;
                }
                $toFormat[] = $args[$name];
            }

            $path = vsprintf($format, $toFormat);

            if (!empty($qstring)) {
                $formatted = http_build_query($qstring);
                $path .= '?' . $formatted;
            }

            return $path;
        }

        $candidates = $this->reverse_statics[$action];

        foreach ($candidates as $candidate) {
            list($tryArgs, $tryPath) = $candidate;

            foreach ($tryArgs as $key => $value) {
                if (!array_key_exists($key, $args) || $args[$key] != $value) {
                    // next candidate
                    continue 2;
                }
            }

            // success
            $path = $tryPath;

            if (!empty($qstring)) {
                $formatted = http_build_query($qstring);
                $path .= '?' . $formatted;
            }

            return $path;
        }

        // failure; some reporting twiddles

        unset($args['action']);

        if (empty($args)) {
            throw new Exception(sprintf('No matches for action "%s"', $action));
        }

        $argstring = '';

        foreach ($args as $key => $value) {
            $argstring .= "$key=$value ";
        }

        throw new Exception(sprintf('No matches for action "%s" with arguments "%s"', $action, $argstring));
    }

    protected function getParamNames($path)
    {
        preg_match_all('/:(?P<name>\w+)/', $path, $match);
        return $match['name'];
    }

    public static function makeRegex($path, $paramPatterns)
    {
        $pr = new PatternReplacer($paramPatterns);

        $regex = preg_replace_callback('/:(\w+)/',
                                       [$pr, 'toPattern'],
                                       $path);

        $regex = '#^' . str_replace('#', '\#', $regex) . '$#';

        return $regex;
    }

    protected function makeFormat($path)
    {
        $format = preg_replace('/(:\w+)/', '%s', $path);

        return $format;
    }

    public function getPaths()
    {
        return array_unique($this->allpaths);
    }

    /**
     * Determines whether the route should or not be overwrited.
     * If ACCEPT header isn't set, false will be returned.
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     * @param array $headers accept-headers that should be set to
     * mark the route for overwrite. This array must be associative
     * and contain the headers in the value-set.
     * @return bool true if should overwrite, false otherwise
     */
    public static function should(array $headers): bool
    {
        if (!isset($_SERVER['HTTP_ACCEPT'])) {
            return false;
        }

        $acceptHeader = new AcceptHeader($_SERVER['HTTP_ACCEPT']);
        foreach ($acceptHeader as $ah) {
            if (isset($headers[$ah['raw']])) {
                return true;
            }
        }

        return false;
    }
}

class PatternReplacer
{
    private $patterns;

    public function __construct($patterns)
    {
        $this->patterns = $patterns;
    }

    public function toPattern($matches)
    {
        // trim out the :
        $name = $matches[1];
        if (array_key_exists($name, $this->patterns)) {
            $pattern = $this->patterns[$name];
        } else {
            // ???
            $pattern = '\w+';
        }
        return '(?P<'.$name.'>'.$pattern.')';
    }
}
