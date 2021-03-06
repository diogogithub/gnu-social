<?php
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

/**
 * Base class for all actions (~views)
 *
 * @category  Action
 * @package   GNUsocial
 *
 * @author    Evan Prodromou <evan@status.net>
 * @author    Sarven Capadisli <csarven@status.net>
 * @copyright 2008 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
defined('GNUSOCIAL') || die();

/**
 * Base class for all actions
 *
 * This is the base class for all actions in the package. An action is
 * more or less a "view" in an MVC framework.
 *
 * Actions are responsible for extracting and validating parameters; using
 * model classes to read and write to the database; and doing ouput.
 *
 * @category  Output
 *
 * @copyright 2008 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 *
 * @see       HTMLOutputter
 */
class action extends HTMLOutputter // lawsuit
{
    // This should be protected/private in the future
    public $args = [];

    // Action properties, set per-class
    protected $action = false;
    protected $ajax = false;
    protected $menus = true;
    protected $needLogin = false;
    protected $redirectAfterLogin = false;
    protected $needPost = false;    // implies canPost if true
    protected $canPost = false;     // can this action handle POST method?

    // The currently scoped profile (normally Profile::current; from $this->auth_user for API)
    protected $scoped;

    // Related to front-end user representation
    protected $format;
    protected $error;
    protected $msg;

    /**
     * Constructor
     *
     * Just wraps the HTMLOutputter constructor.
     *
     * @param string $output URI to output to, default = stdout
     * @param ?bool  $indent Whether to indent output, if null it defaults to true
     *
     * @throws ServerException
     *
     * @see XMLOutputter::__construct
     * @see HTMLOutputter::__construct
     */
    public function __construct($output = 'php://output', $indent = null)
    {
        parent::__construct($output, $indent);
    }

    /**
     * @param array $args
     * @param string $output
     * @param null|bool $indent
     *
     * @return mixed
     */
    public static function run(array $args = [], $output = 'php://output', $indent = null)
    {
        $class = get_called_class();
        $action = new $class($output, $indent);
        set_exception_handler([$action, 'handleError']);
        $action->execute($args);
        return $action;
    }

    /**
     * @return null|string
     */
    public function getInfo(): ?string
    {
        return $this->msg;
    }

    /**
     * @param mixed $e
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function handleError($e): void
    {
        if ($e instanceof ClientException) {
            $this->clientError($e->getMessage(), $e->getCode());
        } elseif ($e instanceof ServerException) {
            $this->serverError($e->getMessage(), $e->getCode());
        } else {
            // If it wasn't specified more closely which kind of exception it was
            $this->serverError($e->getMessage(), 500);
        }
    }

    /**
     * Client error
     *
     * @param string $msg error message to display
     * @param int $code http error code, 400 by default
     * @param null|string $format error format (json, xml, text) for ApiAction
     *
     * @return void
     * @throws ServerException
     *
     * @throws ClientException
     */
    public function clientError(string $msg, int $code = 400, ?string $format = null): void
    {
        // $format is currently only relevant for an ApiAction anyway
        if ($format === null) {
            $format = $this->format;
        }

        common_debug("User error '{$code}' on '{$this->action}': {$msg}", __FILE__);

        if (!array_key_exists($code, ClientErrorAction::$status)) {
            $code = 400;
        }

        switch ($format) {
            case 'xml':
                http_response_code($code);
                $this->initDocument('xml');
                $this->elementStart('hash');
                $this->element('error', null, $msg);
                $this->element('request', null, $_SERVER['REQUEST_URI']);
                $this->elementEnd('hash');
                $this->endDocument('xml');
                break;
            case 'json':
                if (!isset($this->callback)) {
                    http_response_code($code);
                }
                $this->initDocument('json');
                $error_array = ['error' => $msg, 'request' => $_SERVER['REQUEST_URI']];
                echo json_encode($error_array);
                $this->endDocument('json');
                break;
            case 'text':
                http_response_code($code);
                header('Content-Type: text/plain; charset=utf-8');
                echo $msg;
                break;
            default:
                common_log(LOG_ERR, 'Handled clientError (' . _ve($code) . ') but cannot output into desired format (' . _ve($this->format) . '): ' . _ve($msg));
                $action = new ClientErrorAction($msg, $code);
                $action->execute();
        }
        exit((int)$code);
    }

    /**
     * @param array $args
     *
     * @throws ClientException
     * @throws ServerException
     */
    public function execute(array $args = []): void
    {
        // checkMirror stuff
        if (common_config('db', 'mirror') && $this->isReadOnly($args)) {
            if (is_array(common_config('db', 'mirror'))) {
                // "load balancing", ha ha
                $arr = common_config('db', 'mirror');
                $k = array_rand($arr);
                $mirror = $arr[$k];
            } else {
                $mirror = common_config('db', 'mirror');
            }

            // everyone else uses the mirror
            common_config_set('db', 'database', $mirror);
        }

        if (Event::handle('StartActionExecute', [$this, &$args])) {
            $prepared = $this->prepare($args);
            if ($prepared) {
                $this->handle();
            } else {
                common_debug('Prepare failed for Action.');
            }

            $this->flush();
            Event::handle('EndActionExecute', [$this]);
        }
    }

    /**
     * Return true if read only.
     *
     * MAY override
     *
     * @param array $args other arguments
     *
     * @return bool is read only action?
     */
    public function isReadOnly($args)
    {
        return false;
    }

    /**
     * For initializing members of the class.
     *
     * @param array $args misc. arguments
     *
     * @return bool true
     * @throws ServerException
     *
     * @throws ClientException
     */
    protected function prepare(array $args = [])
    {
        if ($this->needPost && !$this->isPost()) {
            // TRANS: Client error. POST is a HTTP command. It should not be translated.
            $this->clientError(_('This method requires a POST.'), 405);
        }

        // needPost, of course, overrides canPost if true
        if (!$this->canPost) {
            $this->canPost = $this->needPost;
        }

        $this->args = common_copy_args($args);

        // This could be set with get_called_action and then
        // chop off 'Action' from the class name. In lower case.
        $this->action = strtolower($this->trimmed('action'));

        if ($this->ajax || $this->boolean('ajax')) {
            // check with GNUsocial::isAjax()
            GNUsocial::setAjax(true);
        }

        if ($this->needLogin) {
            $this->checkLogin(); // if not logged in, this redirs/excepts
        }

        if ($this->redirectAfterLogin) {
            common_set_returnto($this->selfUrl());
        }

        $this->updateScopedProfile();

        return true;
    }

    /**
     * Check if the current request is a POST
     *
     * @return bool true if POST; otherwise false.
     */
    public function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] == 'POST';
    }

    /**
     * Returns trimmed query argument or default value if not found
     *
     * @param string $key requested argument
     * @param string $def default value to return if $key is not provided
     *
     * @return mixed is read only action?
     */
    public function trimmed(string $key, string $def = null)
    {
        $arg = $this->arg($key, $def);
        return is_string($arg) ? trim($arg) : $arg;
    }

    /**
     * Returns query argument or default value if not found
     *
     * @param string $key requested argument
     * @param string $def default value to return if $key is not provided
     *
     * @return bool|null is read only action?
     */
    public function arg($key, $def = null)
    {
        if (array_key_exists($key, $this->args)) {
            return $this->args[$key];
        }
        return $def;
    }

    /**
     * Boolean understands english (yes, no, true, false)
     *
     * @param string $key query key we're interested in
     * @param bool $def default value
     *
     * @return bool interprets yes/no strings as boolean
     */
    public function boolean($key, $def = false): bool
    {
        $arg = strtolower($this->trimmed($key));

        if (is_null($arg)) {
            return $def;
        }
        if (in_array($arg, ['true', 'yes', '1', 'on'])) {
            return true;
        }
        if (in_array($arg, ['false', 'no', '0'])) {
            return false;
        }
        return $def;
    }

    /**
     * If not logged in, take appropriate action (redir or exception)
     *
     * @param bool $redir Redirect to login if not logged in
     *
     * @return bool true if logged in (never returns if not)
     * @throws ServerException
     *
     * @throws ClientException
     */
    public function checkLogin(bool $redir = true): bool
    {
        if (common_logged_in()) {
            return true;
        }

        if ($redir == true) {
            common_set_returnto($_SERVER['REQUEST_URI']);
            common_redirect(common_local_url('login'));
        }

        // TRANS: Error message displayed when trying to perform an action that requires a logged in user.
        $this->clientError(_('Not logged in.'), 403);
    }

    /**
     * @return null|Profile
     */
    public function updateScopedProfile(): ?Profile
    {
        $this->scoped = Profile::current();
        return $this->scoped;
    }

    /**
     * Handler method
     *
     * @return void
     */
    protected function handle()
    {
        header('Vary: Accept-Encoding,Cookie');

        $lm = $this->lastModified();
        $etag = $this->etag();

        if ($etag) {
            header('ETag: ' . $etag);
        }

        if ($lm) {
            header('Last-Modified: ' . date(DATE_RFC1123, $lm));
            if ($this->isCacheable()) {
                header('Expires: ' . gmdate('D, d M Y H:i:s', 0) . ' GMT');
                header('Cache-Control: private, must-revalidate, max-age=0');
                header('Pragma:');
            }
        }

        $checked = false;
        if ($etag) {
            $if_none_match = (array_key_exists('HTTP_IF_NONE_MATCH', $_SERVER)) ?
                $_SERVER['HTTP_IF_NONE_MATCH'] : null;
            if ($if_none_match) {
                // If this check fails, ignore the if-modified-since below.
                $checked = true;
                if ($this->_hasEtag($etag, $if_none_match)) {
                    http_response_code(304);
                    // Better way to do this?
                    exit(0);
                }
            }
        }

        if (!$checked && $lm && array_key_exists('HTTP_IF_MODIFIED_SINCE', $_SERVER)) {
            $if_modified_since = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
            $ims = strtotime($if_modified_since);
            if ($lm <= $ims) {
                http_response_code(304);
                // Better way to do this?
                exit(0);
            }
        }
    }

    /**
     * Return last modified, if applicable.
     *
     * MAY override
     *
     * @return null|string last modified http header
     */
    public function lastModified()
    {
        // For comparison with If-Last-Modified
        // If not applicable, return null
        return null;
    }

    /**
     * Return etag, if applicable.
     *
     * MAY override
     *
     * @return null|string etag http header
     */
    public function etag()
    {
        return null;
    }

    /**
     * Is this action cacheable?
     *
     * If the action returns a last-modified
     *
     * @return bool is read only action?
     */
    public function isCacheable(): bool
    {
        return true;
    }

    /**
     * Has etag? (private)
     *
     * @param string $etag etag http header
     * @param string $if_none_match ifNoneMatch http header
     *
     * @return bool
     */
    public function _hasEtag(string $etag, string $if_none_match): bool
    {
        $etags = explode(',', $if_none_match);
        return in_array($etag, $etags) || in_array('*', $etags);
    }

    /**
     * Server error
     *
     * @param string $msg error message to display
     * @param int $code http error code, 500 by default
     * @param null|string $format
     *
     * @return void
     * @throws ServerException
     *
     * @throws ClientException
     */
    public function serverError(string $msg, int $code = 500, ?string $format = null): void
    {
        if ($format === null) {
            $format = $this->format;
        }

        common_debug("Server error '{$code}' on '{$this->action}': {$msg}", __FILE__);

        if (!array_key_exists($code, ServerErrorAction::$status)) {
            $code = 500;
        }

        switch ($format) {
            case 'xml':
                http_response_code($code);
                $this->initDocument('xml');
                $this->elementStart('hash');
                $this->element('error', null, $msg);
                $this->element('request', null, $_SERVER['REQUEST_URI']);
                $this->elementEnd('hash');
                $this->endDocument('xml');
                break;
            case 'json':
                if (!isset($this->callback)) {
                    http_response_code($code);
                }
                $this->initDocument('json');
                $error_array = ['error' => $msg, 'request' => $_SERVER['REQUEST_URI']];
                echo json_encode($error_array);
                $this->endDocument('json');
                break;
            default:
                common_log(LOG_ERR, 'Handled serverError (' . _ve($code) . ') but cannot output into desired format (' . _ve($this->format) . '): ' . _ve($msg));
                $action = new ServerErrorAction($msg, $code);
                $action->execute();
        }

        exit((int)$code);
    }

    /**
     * @return null|Profile
     */
    public function getScoped(): ?Profile
    {
        return ($this->scoped instanceof Profile) ? $this->scoped : null;
    }

    /**
     * @param array $names
     *
     * @return bool
     */
    public function isAction(array $names): bool
    {
        foreach ($names as $class) {
            // PHP is case insensitive, and we have stuff like ApiUpperCaseAction,
            // but we at least make a point out of wanting to do stuff case-sensitive.
            $class = ucfirst($class) . 'Action';
            if ($this instanceof $class) {
                return true;
            }
        }
        return false;
    }

    /**
     * Show page, a template method.
     *
     * @return void
     * @throws ReflectionException
     * @throws ServerException
     *
     * @throws ClientException
     */
    public function showPage()
    {
        if (GNUsocial::isAjax()) {
            self::showAjax();
            return;
        }
        if (Event::handle('StartShowHTML', [$this])) {
            $this->startHTML();
            $this->flush();
            Event::handle('EndShowHTML', [$this]);
        }
        if (Event::handle('StartShowHead', [$this])) {
            $this->showHead();
            $this->flush();
            Event::handle('EndShowHead', [$this]);
        }
        if (Event::handle('StartShowBody', [$this])) {
            $this->showBody();
            Event::handle('EndShowBody', [$this]);
        }
        if (Event::handle('StartEndHTML', [$this])) {
            $this->endHTML();
            Event::handle('EndEndHTML', [$this]);
        }
    }

    /**
     * @return void
     * @throws ServerException
     *
     * @throws ClientException
     */
    public function showAjax()
    {
        $this->startHTML('text/xml;charset=utf-8');
        $this->elementStart('head');
        // TRANS: Title for conversation page.
        $this->element('title', null, $this->title());
        $this->elementEnd('head');
        $this->elementStart('body');
        if ($this->getError()) {
            $this->element('p', ['id' => 'error'], $this->getError());
        } else {
            $this->showContent();
        }
        $this->elementEnd('body');
        $this->endHTML();
    }

    /**
     * Returns the page title
     *
     * SHOULD overload
     *
     * @return string page title
     */
    public function title()
    {
        // TRANS: Page title for a page without a title set.
        return _m('Untitled page');
    }

    /**
     * @return null|string
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Show content.
     *
     * MUST overload (unless there's not a notice)
     *
     * @return void
     */
    protected function showContent()
    {
    }

    public function endHTML(): void
    {
        global $_startCpuTime;

        if (isset($_startCpuTime)) {
            $end_cpu_time = hrtime(true);
            $diff = round(($end_cpu_time - $_startCpuTime) / 1000000);
            $this->raw("<!-- {$diff}ms -->");
        }

        parent::endHTML();
    }

    /**
     * Show head, a template method.
     *
     * @return void
     * @throws ServerException
     */
    public function showHead()
    {
        // XXX: attributes (profile?)
        $this->elementStart('head');
        if (Event::handle('StartShowHeadElements', [$this])) {
            if (Event::handle('StartShowHeadTitle', [$this])) {
                $this->showTitle();
                Event::handle('EndShowHeadTitle', [$this]);
            }
            $this->showShortcutIcon();
            $this->showStylesheets();
            $this->showOpenSearch();
            $this->showFeeds();
            $this->showDescription();
            $this->extraHead();
            Event::handle('EndShowHeadElements', [$this]);
        }
        $this->elementEnd('head');
    }

    /**
     * Show title, a template method.
     *
     * @return void
     * @throws ServerException
     *
     */
    public function showTitle()
    {
        $this->element(
            'title',
            null,
            // TRANS: Page title. %1$s is the title, %2$s is the site name.
            sprintf(
                _('%1$s - %2$s'),
                $this->title(),
                common_config('site', 'name')
            )
        );
    }

    /**
     * Show themed shortcut icon
     *
     * @return void
     * @throws ServerException
     *
     */
    public function showShortcutIcon()
    {
        if (is_readable(INSTALLDIR . '/theme/' . common_config('site', 'theme') . '/favicon.ico')) {
            $this->element('link', ['rel' => 'shortcut icon',
                'href' => Theme::path('favicon.ico'),]);
        } else {
            // favicon.ico should be HTTPS if the rest of the page is
            $this->element('link', ['rel' => 'shortcut icon',
                'href' => common_path('favicon.ico', GNUsocial::isHTTPS()),]);
        }

        if (common_config('site', 'mobile')) {
            if (is_readable(INSTALLDIR . '/theme/' . common_config('site', 'theme') . '/apple-touch-icon.png')) {
                $this->element('link', ['rel' => 'apple-touch-icon',
                    'href' => Theme::path('apple-touch-icon.png'),]);
            } else {
                $this->element('link', ['rel' => 'apple-touch-icon',
                    'href' => common_path('apple-touch-icon.png'),]);
            }
        }
    }

    /**
     * Show stylesheets
     *
     * @return void
     * @throws ServerException
     *
     */
    public function showStylesheets()
    {
        if (Event::handle('StartShowStyles', [$this])) {

            // Use old name for StatusNet for compatibility on events

            if (Event::handle('StartShowStylesheets', [$this])) {
                $this->primaryCssLink(null, 'screen, projection, tv, print');
                Event::handle('EndShowStylesheets', [$this]);
            }

            $this->cssLink('js/extlib/jquery-ui/css/smoothness/jquery-ui.css');

            if (Event::handle('StartShowUAStyles', [$this])) {
                Event::handle('EndShowUAStyles', [$this]);
            }

            Event::handle('EndShowStyles', [$this]);

            if (common_config('custom_css', 'enabled')) {
                $css = common_config('custom_css', 'css');
                if (Event::handle('StartShowCustomCss', [$this, &$css])) {
                    if (trim($css) != '') {
                        $this->style($css);
                    }
                    Event::handle('EndShowCustomCss', [$this]);
                }
            }
        }
    }

    /**
     * @param null|string $mainTheme
     * @param null|string $media
     *
     * @throws ServerException
     */
    public function primaryCssLink(?string $mainTheme = null, ?string $media = null): void
    {
        $theme = new Theme($mainTheme);

        // Some themes may have external stylesheets
        foreach ($theme->getExternals() as $url) {
            $this->cssLink($url, $mainTheme, $media);
        }

        // If the currently-selected theme has dependencies on other themes,
        // we'll need to load their display.css files as well in order.
        $baseThemes = $theme->getDeps();
        foreach ($baseThemes as $baseTheme) {
            $this->cssLink('css/display.css', $baseTheme, $media);
        }
        $this->cssLink('css/display.css', $mainTheme, $media);

        // Additional styles for RTL languages
        if (is_rtl(common_language())) {
            if (file_exists(Theme::file('css/rtl.css'))) {
                $this->cssLink('css/rtl.css', $mainTheme, $media);
            }
        }
    }

    /**
     * Show OpenSearch headers
     *
     * @return void
     * @throws ServerException
     *
     */
    public function showOpenSearch()
    {
        $this->element('link', ['rel' => 'search',
            'type' => 'application/opensearchdescription+xml',
            'href' => common_local_url('opensearch', ['type' => 'people']),
            'title' => common_config('site', 'name') . ' People Search',]);
        $this->element('link', ['rel' => 'search', 'type' => 'application/opensearchdescription+xml',
            'href' => common_local_url('opensearch', ['type' => 'notice']),
            'title' => common_config('site', 'name') . ' Notice Search',]);
    }

    /**
     * Show feed headers
     *
     * MAY overload
     *
     * @return void
     */
    public function showFeeds()
    {
        foreach ($this->getFeeds() as $feed) {
            $this->element('link', ['rel' => $feed->rel(),
                'href' => $feed->url,
                'type' => $feed->mimeType(),
                'title' => $feed->title,]);
        }
    }

    /**
     * An array of feeds for this action.
     *
     * Returns an array of potential feeds for this action.
     *
     * @return array Feed object to show in head and links
     */
    public function getFeeds()
    {
        return [];
    }

    /**
     * Show description.
     *
     * SHOULD overload
     *
     * @return void
     */
    public function showDescription()
    {
        // does nothing by default
    }

    /**
     * Show extra stuff in <head>.
     *
     * MAY overload
     *
     * @return void
     */
    public function extraHead()
    {
        // does nothing by default
    }

    /**
     * Show body.
     *
     * Calls template methods
     *
     * @return void
     * @throws ReflectionException
     *
     * @throws ServerException
     */
    public function showBody()
    {
        $params = ['id' => $this->getActionName()];
        if ($this->scoped instanceof Profile) {
            $params['class'] = 'user_in';
        }
        $this->elementStart('body', $params);
        $this->elementStart('div', ['id' => 'wrap']);
        if (Event::handle('StartShowHeader', [$this])) {
            $this->showHeader();
            $this->flush();
            Event::handle('EndShowHeader', [$this]);
        }
        $this->showCore();
        $this->flush();
        if (Event::handle('StartShowFooter', [$this])) {
            $this->showFooter();
            $this->flush();
            Event::handle('EndShowFooter', [$this]);
        }
        $this->elementEnd('div');
        $this->showScripts();
        $this->elementEnd('body');
    }

    /**
     * Must be run _after_ prepare
     * @return mixed (string, bool)
     */
    public function getActionName()
    {
        return $this->action;
    }

    /**
     * Show header of the page.
     *
     * Calls template methods
     *
     * @return void
     * @throws ServerException
     *
     */
    public function showHeader()
    {
        $this->elementStart('div', ['id' => 'header']);
        $this->showLogo();
        $this->showPrimaryNav();
        if (Event::handle('StartShowSiteNotice', [$this])) {
            $this->showSiteNotice();

            Event::handle('EndShowSiteNotice', [$this]);
        }

        $this->elementEnd('div');
    }

    /**
     * Show configured logo.
     *
     * @return void
     * @throws ServerException
     *
     */
    public function showLogo()
    {
        $this->elementStart('address', ['id' => 'site_contact', 'class' => 'h-card']);
        if (Event::handle('StartAddressData', [$this])) {
            if (common_config('singleuser', 'enabled')) {
                $user = User::singleUser();
                $url = common_local_url(
                    'showstream',
                    ['nickname' => $user->nickname]
                );
            } elseif (common_logged_in()) {
                $cur = common_current_user();
                $url = common_local_url('all', ['nickname' => $cur->nickname]);
            } else {
                $url = common_local_url('public');
            }

            $this->elementStart('a', ['class' => 'home bookmark',
                'href' => $url,]);

            if (GNUsocial::isHTTPS()) {
                $logoUrl = common_config('site', 'ssllogo');
                if (empty($logoUrl)) {
                    // if logo is an uploaded file, try to fall back to HTTPS file URL
                    $httpUrl = common_config('site', 'logo');
                    if (!empty($httpUrl)) {
                        try {
                            $f = File::getByUrl($httpUrl);
                            if (!empty($f->filename)) {
                                // this will handle the HTTPS case
                                $logoUrl = File::url($f->filename);
                            }
                        } catch (NoResultException $e) {
                            // no match
                        }
                    }
                }
            } else {
                $logoUrl = common_config('site', 'logo');
            }

            if (empty($logoUrl) && file_exists(Theme::file('logo.png'))) {
                // This should handle the HTTPS case internally
                $logoUrl = Theme::path('logo.png');
            }

            if (!empty($logoUrl)) {
                $this->element('img', ['class' => 'logo u-photo p-name',
                    'src' => $logoUrl,
                    'alt' => common_config('site', 'name'),]);
            }

            $this->elementEnd('a');

            Event::handle('EndAddressData', [$this]);
        }
        $this->elementEnd('address');
    }

    /**
     * Show primary navigation.
     *
     * @return void
     * @throws ServerException
     *
     */
    public function showPrimaryNav()
    {
        $this->elementStart('div', ['id' => 'site_nav_global_primary']);

        $user = common_current_user();

        if (!empty($user) || !common_config('site', 'private')) {
            $form = new SearchForm($this);
            $form->show();
        }

        $pn = new PrimaryNav($this);
        $pn->show();
        $this->elementEnd('div');
    }

    /**
     * Show site notice.
     *
     * @return void
     * @throws ServerException
     *
     */
    public function showSiteNotice()
    {
        // Revist. Should probably do an hAtom pattern here
        $text = common_config('site', 'notice');
        if ($text) {
            $this->elementStart('div', ['id' => 'site_notice',
                'class' => 'system_notice',]);
            $this->raw($text);
            $this->elementEnd('div');
        }
    }

    /**
     * Show core.
     *
     * Shows local navigation, content block and aside.
     *
     * @return void
     * @throws ReflectionException
     * @throws UserNoProfileException
     */
    public function showCore()
    {
        $this->elementStart('div', ['id' => 'core']);
        $this->elementStart('div', ['id' => 'aside_primary_wrapper']);
        $this->elementStart('div', ['id' => 'content_wrapper']);
        $this->elementStart('div', ['id' => 'site_nav_local_views_wrapper']);
        if (Event::handle('StartShowLocalNavBlock', [$this])) {
            $this->showLocalNavBlock();
            $this->flush();
            Event::handle('EndShowLocalNavBlock', [$this]);
        }
        if (Event::handle('StartShowContentBlock', [$this])) {
            $this->showContentBlock();
            $this->flush();
            Event::handle('EndShowContentBlock', [$this]);
        }
        if (Event::handle('StartShowAside', [$this])) {
            $this->showAside();
            $this->flush();
            Event::handle('EndShowAside', [$this]);
        }
        $this->elementEnd('div');
        $this->elementEnd('div');
        $this->elementEnd('div');
        $this->elementEnd('div');
    }

    /**
     * Show local navigation block.
     *
     * @return void
     */
    public function showLocalNavBlock()
    {
        // Need to have this ID for CSS; I'm too lazy to add it to
        // all menus
        $this->elementStart('div', ['id' => 'site_nav_local_views']);
        // Cheat cheat cheat!
        $this->showLocalNav();
        $this->elementEnd('div');
    }

    /**
     * Show local navigation.
     *
     * SHOULD overload
     *
     * @return void
     */
    public function showLocalNav()
    {
        $nav = new DefaultLocalNav($this);
        $nav->show();
    }

    /**
     * Show content block.
     *
     * @return void
     * @throws ReflectionException
     * @throws UserNoProfileException
     */
    public function showContentBlock()
    {
        $this->elementStart('div', ['id' => 'content']);
        if (common_logged_in()) {
            if (Event::handle('StartShowNoticeForm', [$this])) {
                $this->showNoticeForm();
                Event::handle('EndShowNoticeForm', [$this]);
            }
        }
        if (Event::handle('StartShowPageTitle', [$this])) {
            $this->showPageTitle();
            Event::handle('EndShowPageTitle', [$this]);
        }
        $this->showPageNoticeBlock();
        $this->elementStart('div', ['id' => 'content_inner']);
        // show the actual content (forms, lists, whatever)
        $this->showContent();
        $this->elementEnd('div');
        $this->elementEnd('div');
    }

    /**
     * Show notice form.
     *
     * MAY overload if no notice form needed... or direct message box????
     *
     * @return void
     * @throws UserNoProfileException
     *
     */
    public function showNoticeForm()
    {
        // TRANS: Tab on the notice form.
        $tabs = ['status' => ['title' => _m('TAB', 'Status'),
            'href' => common_local_url('newnotice'),]];

        $this->elementStart('div', 'input_forms');

        $this->element('label', ['for' => 'input_form_nav'], _m('TAB', 'Share your:'));

        if (Event::handle('StartShowEntryForms', [&$tabs])) {
            $this->elementStart('ul', ['class' => 'nav',
                'id' => 'input_form_nav',]);

            foreach ($tabs as $tag => $data) {
                $tag = htmlspecialchars($tag);
                $attrs = ['id' => 'input_form_nav_' . $tag,
                    'class' => 'input_form_nav_tab',];

                if ($tag == 'status') {
                    $attrs['class'] .= ' current';
                }
                $this->elementStart('li', $attrs);

                $this->element(
                    'a',
                    ['onclick' => 'return SN.U.switchInputFormTab("' . $tag . '");',
                        'href' => $data['href'],],
                    $data['title']
                );
                $this->elementEnd('li');
            }

            $this->elementEnd('ul');

            foreach ($tabs as $tag => $data) {
                $attrs = ['class' => 'input_form',
                    'id' => 'input_form_' . $tag,];
                if ($tag == 'status') {
                    $attrs['class'] .= ' current';
                }

                $this->elementStart('div', $attrs);

                $form = null;

                if (Event::handle('StartMakeEntryForm', [$tag, $this, &$form])) {
                    if ($tag == 'status') {
                        $options = $this->noticeFormOptions();
                        $form = new NoticeForm($this, $options);
                    }
                    Event::handle('EndMakeEntryForm', [$tag, $this, $form]);
                }

                if (!empty($form)) {
                    $form->show();
                }

                $this->elementEnd('div');
            }
        }

        $this->elementEnd('div');
    }

    /**
     * @return array
     */
    public function noticeFormOptions()
    {
        return [];
    }

    /**
     * Show page title.
     *
     * @return void
     */
    public function showPageTitle()
    {
        $this->element('h1', null, $this->title());
    }

    /**
     * Show page notice block.
     *
     * Only show the block if a subclassed action has overrided
     * Action::showPageNotice(), or an event handler is registered for
     * the StartShowPageNotice event, in which case we assume the
     * 'page_notice' definition list is desired.  This is to prevent
     * empty 'page_notice' definition lists from being output everywhere.
     *
     * @return void
     * @throws ReflectionException
     *
     */
    public function showPageNoticeBlock()
    {
        $rmethod = new ReflectionMethod($this, 'showPageNotice');
        $dclass = $rmethod->getDeclaringClass()->getName();

        if ($dclass != 'Action' || Event::hasHandler('StartShowPageNotice')) {
            $this->elementStart('div', ['id' => 'page_notice',
                'class' => 'system_notice',]);
            if (Event::handle('StartShowPageNotice', [$this])) {
                $this->showPageNotice();
                Event::handle('EndShowPageNotice', [$this]);
            }
            $this->elementEnd('div');
        }
    }

    /**
     * Show page notice.
     *
     * SHOULD overload (unless there's not a notice)
     *
     * @return void
     */
    public function showPageNotice()
    {
    }

    /**
     * Show Aside.
     *
     * @return void
     * @throws ReflectionException
     *
     */
    public function showAside()
    {
        $this->elementStart('div', ['id' => 'aside_primary',
            'class' => 'aside',]);
        $this->showProfileBlock();
        if (Event::handle('StartShowObjectNavBlock', [$this])) {
            $this->showObjectNavBlock();
            Event::handle('EndShowObjectNavBlock', [$this]);
        }
        if (Event::handle('StartShowSections', [$this])) {
            $this->showSections();
            Event::handle('EndShowSections', [$this]);
        }
        if (Event::handle('StartShowExportData', [$this])) {
            $this->showExportData();
            Event::handle('EndShowExportData', [$this]);
        }
        $this->elementEnd('div');
    }

    /**
     * If there's a logged-in user, show a bit of login context
     *
     * @return void
     * @throws Exception
     *
     */
    public function showProfileBlock()
    {
        if (common_logged_in()) {
            $block = new DefaultProfileBlock($this);
            $block->show();
        }
    }

    /**
     * Show menu for an object (group, profile)
     *
     * This block will only show if a subclass has overridden
     * the showObjectNav() method.
     *
     * @return void
     * @throws ReflectionException
     *
     */
    public function showObjectNavBlock()
    {
        $rmethod = new ReflectionMethod($this, 'showObjectNav');
        $dclass = $rmethod->getDeclaringClass()->getName();

        if ($dclass != 'Action') {
            // Need to have this ID for CSS; I'm too lazy to add it to
            // all menus
            $this->elementStart('div', ['id' => 'site_nav_object',
                'class' => 'section',]);
            $this->showObjectNav();
            $this->elementEnd('div');
        }
    }

    /**
     * Show object navigation.
     *
     * If there are things to do with this object, show it here.
     *
     * @return void
     */
    public function showObjectNav()
    {
        // Nothing here.
    }

    /**
     * Show sections.
     *
     * SHOULD overload
     *
     * @return void
     */
    public function showSections()
    {
        // for each section, show it
    }

    /**
     * Show export data feeds.
     *
     * @return void
     */
    public function showExportData()
    {
        $feeds = $this->getFeeds();
        if (!empty($feeds)) {
            $fl = new FeedList($this, $feeds);
            $fl->show();
        }
    }

    /**
     * Show footer.
     *
     * @return void
     * @throws ServerException
     */
    public function showFooter()
    {
        $this->elementStart('div', ['id' => 'footer']);
        if (Event::handle('StartShowInsideFooter', [$this])) {
            $this->showSecondaryNav();
            $this->showLicenses();
            Event::handle('EndShowInsideFooter', [$this]);
        }
        $this->elementEnd('div');
    }

    /**
     * Show secondary navigation.
     *
     * @return void
     */
    public function showSecondaryNav()
    {
        $sn = new SecondaryNav($this);
        $sn->show();
    }

    /**
     * Show licenses.
     *
     * @return void
     * @throws ServerException
     */
    public function showLicenses()
    {
        $this->showGNUsocialLicense();
        $this->showContentLicense();
    }

    /**
     * Show GNU social license.
     *
     * @return void
     * @throws ServerException
     *
     */
    public function showGNUsocialLicense()
    {
        if (common_config('site', 'broughtby')) {
            // TRANS: First sentence of the GNU social site license. Used if 'broughtby' is set.
            // TRANS: Text between [] is a link description, text between () is the link itself.
            // TRANS: Make sure there is no whitespace between "]" and "(".
            // TRANS: "%%site.broughtby%%" is the value of the variable site.broughtby
            $instr = _('**%%site.name%%** is a social network, courtesy of [%%site.broughtby%%](%%site.broughtbyurl%%).');
        } else {
            // TRANS: First sentence of the GNU social site license. Used if 'broughtby' is not set.
            $instr = _('**%%site.name%%** is a social network.');
        }
        $instr .= ' ';
        // TRANS: Second sentence of the GNU social site license. Mentions the GNU social source code license.
        // TRANS: Make sure there is no whitespace between "]" and "(".
        // TRANS: [%1$s](%2$s) is a link description followed by the link itself
        // TRANS: %3$s is the version of GNU social that is being used.
        $instr .= sprintf(_('It runs on [%1$s](%2$s), version %3$s, available under the [GNU Affero General Public License](http://www.fsf.org/licensing/licenses/agpl-3.0.html).'), GNUSOCIAL_ENGINE, GNUSOCIAL_ENGINE_URL, GNUSOCIAL_VERSION);
        $output = common_markup_to_html($instr);
        $this->raw($output);
        // do it
    }

    /**
     * Show content license.
     *
     * @return void
     * @throws ServerException
     *
     */
    public function showContentLicense()
    {
        if (Event::handle('StartShowContentLicense', [$this])) {
            switch (common_config('license', 'type')) {
                case 'private':
                    // TRANS: Content license displayed when license is set to 'private'.
                    // TRANS: %1$s is the site name.
                    $this->element('p', null, sprintf(
                        _('Content and data of %1$s are private and confidential.'),
                        common_config('site', 'name')
                    ));
                // fall through
                // no break
                case 'allrightsreserved':
                    if (common_config('license', 'owner')) {
                        // TRANS: Content license displayed when license is set to 'allrightsreserved'.
                        // TRANS: %1$s is the copyright owner.
                        $this->element('p', null, sprintf(
                            _('Content and data copyright by %1$s. All rights reserved.'),
                            common_config('license', 'owner')
                        ));
                    } else {
                        // TRANS: Content license displayed when license is set to 'allrightsreserved' and no owner is set.
                        $this->element('p', null, _('Content and data copyright by contributors. All rights reserved.'));
                    }
                    break;
                case 'cc': // fall through
                default:
                    $this->elementStart('p');

                    $image = common_config('license', 'image');
                    $sslimage = common_config('license', 'sslimage');

                    if (GNUsocial::isHTTPS()) {
                        if (!empty($sslimage)) {
                            $url = $sslimage;
                        } elseif (preg_match('#^http://i.creativecommons.org/#', $image)) {
                            // CC support HTTPS on their images
                            $url = preg_replace('/^http/', 'https', $image, 1);
                        } else {
                            // Better to show mixed content than no content
                            $url = $image;
                        }
                    } else {
                        $url = $image;
                    }

                    $this->element('img', ['id' => 'license_cc',
                        'src' => $url,
                        'alt' => common_config('license', 'title'),
                        'width' => '80',
                        'height' => '15',]);
                    $this->text(' ');
                    // TRANS: license message in footer.
                    // TRANS: %1$s is the site name, %2$s is a link to the license URL, with a licence name set in configuration.
                    $notice = _('All %1$s content and data are available under the %2$s license.');
                    $link = sprintf(
                        '<a class="license" rel="external license" href="%1$s">%2$s</a>',
                        htmlspecialchars(common_config('license', 'url')),
                        htmlspecialchars(common_config('license', 'title'))
                    );
                    $this->raw(@sprintf(
                        htmlspecialchars($notice),
                        htmlspecialchars(common_config('site', 'name')),
                        $link
                    ));
                    $this->elementEnd('p');
                    break;
            }

            Event::handle('EndShowContentLicense', [$this]);
        }
    }

    /**
     * Show javascript headers
     *
     * @return void
     * @throws ServerException
     *
     */
    public function showScripts()
    {
        if (Event::handle('StartShowScripts', [$this])) {
            if (Event::handle('StartShowJQueryScripts', [$this])) {
                $this->script('extlib/jquery.js');
                $this->script('extlib/jquery.form.js');
                $this->script('extlib/jquery-ui/jquery-ui.js');
                $this->script('extlib/jquery.cookie.js');

                Event::handle('EndShowJQueryScripts', [$this]);
            }
            if (Event::handle('StartShowStatusNetScripts', [$this])) {
                $this->script('util.js');
                $this->script('xbImportNode.js');

                // This route isn't available in single-user mode.
                // Not sure why, but it causes errors here.
                $this->inlineScript('var _peopletagAC = "' .
                    common_local_url('peopletagautocomplete') . '";');
                $this->showScriptMessages();
                $this->showScriptVariables();
                // Anti-framing code to avoid clickjacking attacks in older browsers.
                // This will show a blank page if the page is being framed, which is
                // consistent with the behavior of the 'X-Frame-Options: SAMEORIGIN'
                // header, which prevents framing in newer browser.
                if (common_config('javascript', 'bustframes')) {
                    $this->inlineScript('if (window.top !== window.self) { document.write = ""; window.top.location = window.self.location; setTimeout(function () { document.body.innerHTML = ""; }, 1); window.self.onload = function () { document.body.innerHTML = ""; }; }');
                }
                Event::handle('EndShowStatusNetScripts', [$this]);
            }
            Event::handle('EndShowScripts', [$this]);
        }
    }

    /**
     * Exports a map of localized text strings to JavaScript code.
     *
     * Plugins can add to what's exported by hooking the StartScriptMessages or EndScriptMessages
     * events and appending to the array. Try to avoid adding strings that won't be used, as
     * they'll be added to HTML output.
     *
     * @return array
     * @throws Exception
     *
     */
    public function showScriptMessages()
    {
        $messages = [];

        if (Event::handle('StartScriptMessages', [$this, &$messages])) {
            // Common messages needed for timeline views etc...

            // TRANS: Localized tooltip for '...' expansion button on overlong remote messages.
            $messages['showmore_tooltip'] = _m('TOOLTIP', 'Show more');
            $messages['popup_close_button'] = _m('TOOLTIP', 'Close popup');

            $messages = array_merge($messages, $this->getScriptMessages());

            Event::handle('EndScriptMessages', [$this, &$messages]);
        }

        if (!empty($messages)) {
            $this->inlineScript('SN.messages=' . json_encode($messages));
        }

        return $messages;
    }

    /**
     * If the action will need localizable text strings, export them here like so:
     *
     * return array('pool_deepend' => _('Deep end'),
     *              'pool_shallow' => _('Shallow end'));
     *
     * The exported map will be available via SN.msg() to JS code:
     *
     *   $('#pool').html('<div class="deepend"></div><div class="shallow"></div>');
     *   $('#pool .deepend').text(SN.msg('pool_deepend'));
     *   $('#pool .shallow').text(SN.msg('pool_shallow'));
     *
     * Exports a map of localized text strings to JavaScript code.
     *
     * Plugins can add to what's exported on any action by hooking the StartScriptMessages or
     * EndScriptMessages events and appending to the array. Try to avoid adding strings that won't
     * be used, as they'll be added to HTML output.
     *
     * @return array
     */
    public function getScriptMessages(): array
    {
        return [];
    }

    /**
     * @return array
     */
    protected function showScriptVariables()
    {
        $vars = [];

        if (Event::handle('StartScriptVariables', [$this, &$vars])) {
            $vars['urlNewNotice'] = common_local_url('newnotice');
            $vars['xhrTimeout'] = ini_get('max_execution_time') * 1000;   // milliseconds
            Event::handle('EndScriptVariables', [$this, &$vars]);
        }

        $this->inlineScript('SN.V = ' . json_encode($vars) . ';');

        return $vars;
    }

    /**
     * Show anonymous message.
     *
     * SHOULD overload
     *
     * @return void
     */
    public function showAnonymousMessage()
    {
        // needs to be defined by the class
    }

    /**
     * Integer value of an argument
     *
     * @param string $key query key we're interested in
     * @param null|string $defValue optional default value (default null)
     * @param null|string $maxValue optional max value (default null)
     * @param null|string $minValue optional min value (default null)
     *
     * @return null|int integer value
     */
    public function int(string $key, ?string $defValue = null, ?string $maxValue = null, ?string $minValue = null): ?int
    {
        $arg = (int)($this->arg($key));

        if (!is_numeric($this->arg($key)) || $arg != $this->arg($key)) {
            return $defValue;
        }

        if (!is_null($maxValue)) {
            $arg = min($arg, $maxValue);
        }

        if (!is_null($minValue)) {
            $arg = max($arg, $minValue);
        }

        return $arg;
    }

    /**
     * Returns the current URL
     *
     * @return string current URL
     * @throws ServerException
     *
     */
    public function selfUrl(): string
    {
        list($action, $args) = $this->returnToArgs();
        return common_local_url($action, $args);
    }

    /**
     * Returns arguments sufficient for re-constructing URL
     *
     * @return array two elements: action, other args
     * @throws ServerException
     *
     */
    public function returnToArgs(): array
    {
        $action = $this->getActionName();
        $args = $this->args;
        unset($args['action']);
        if (common_config('site', 'fancy')) {
            unset($args['p']);
        }
        if (array_key_exists('submit', $args)) {
            unset($args['submit']);
        }
        foreach (array_keys($_COOKIE) as $cookie) {
            unset($args[$cookie]);
        }
        return [$action, $args];
    }

    /**
     * Generate a menu item
     *
     * @param string $url menu URL
     * @param null|string $text menu name
     * @param string $title title attribute, null by default
     * @param bool $is_selected current menu item, false by default
     * @param null|string $id element id, null by default
     * @param null|mixed $class
     *
     * @return void
     */
    public function menuItem(
        string $url,
        $text,
        ?string $title = null,
        bool $is_selected = false,
        ?string $id = null,
        $class = null
    ): void {
        // Added @id to li for some control.
        // XXX: We might want to move this to htmloutputter.php
        $lattrs = [];
        $classes = [];
        if ($class !== null) {
            $classes[] = trim($class);
        }
        if ($is_selected) {
            $classes[] = 'current';
        }

        if (!empty($classes)) {
            $lattrs['class'] = implode(' ', $classes);
        }

        if (!is_null($id)) {
            $lattrs['id'] = $id;
        }

        $this->elementStart('li', $lattrs);
        $attrs['href'] = $url;
        if ($title) {
            $attrs['title'] = $title;
        }
        $this->element('a', $attrs, $text);
        $this->elementEnd('li');
    }

    /**
     * Generate pagination links
     *
     * @param bool $have_before is there something before?
     * @param bool $have_after is there something after?
     * @param int $page current page
     * @param string $action current action
     * @param null|array $args rest of query arguments
     *
     * XXX: The messages in this pagination method only tailor to navigating
     * notices. In other lists, "Previous"/"Next" type navigation is
     * desirable, but not available.
     *
     * @return void
     */
    public function pagination(bool $have_before, bool $have_after, int $page, string $action, ?array $args = null): void
    {
        // Does a little before-after block for next/prev page
        if ($have_before || $have_after) {
            $this->elementStart('ul', ['class' => 'nav',
                'id' => 'pagination',]);
        }
        if ($have_before) {
            $pargs = ['page' => $page - 1];
            $this->elementStart('li', ['class' => 'nav_prev']);
            $this->element(
                'a',
                ['href' => common_local_url($action, $args, $pargs),
                    'rel' => 'prev',],
                // TRANS: Pagination message to go to a page displaying information more in the
                // TRANS: present than the currently displayed information.
                _('After')
            );
            $this->elementEnd('li');
        }
        if ($have_after) {
            $pargs = ['page' => $page + 1];
            $this->elementStart('li', ['class' => 'nav_next']);
            $this->element(
                'a',
                ['href' => common_local_url($action, $args, $pargs),
                    'rel' => 'next',],
                // TRANS: Pagination message to go to a page displaying information more in the
                // TRANS: past than the currently displayed information.
                _('Before')
            );
            $this->elementEnd('li');
        }
        if ($have_before || $have_after) {
            $this->elementEnd('ul');
        }
    }

    /**
     * Check the session token.
     *
     * Checks that the current form has the correct session token,
     * and throw an exception if it does not.
     *
     * @return void
     * @throws ServerException
     * @throws ClientException
     */
    public function checkSessionToken(): void
    {
        // CSRF protection
        $token = $this->trimmed('token');
        if (empty($token) || $token != common_session_token()) {
            // TRANS: Client error text when there is a problem with the session token.
            $this->clientError(_m('There was a problem with your session token.'));
        }
    }
}
