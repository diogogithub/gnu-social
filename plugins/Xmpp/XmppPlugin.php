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
 * Send and receive notices using the XMPP network
 *
 * @category  IM
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Plugin for XMPP
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class XmppPlugin extends ImPlugin
{
    const PLUGIN_VERSION = '2.0.0';

    public $server = null;
    public $port = 5222;
    public $user = 'update';
    public $resource = 'gnusocial';
    public $encryption = true;
    public $password = null;
    public $host = null;  // only set if != server
    public $debug = false; // print extra debug info

    public $transport = 'xmpp';

    public function getDisplayName()
    {
        // TRANS: Plugin display name.
        return _m('XMPP/Jabber');
    }

    public function daemonScreenname()
    {
        $ret = $this->user . '@' . $this->server;
        if ($this->resource) {
            return $ret . '/' . $this->resource;
        } else {
            return $ret;
        }
    }

    public function validate($screenname)
    {
        return $this->validateBaseJid($screenname, common_config('email', 'check_domain'));
    }

    /**
     * Checks whether a string is a syntactically valid base Jabber ID (JID).
     * A base JID won't include a resource specifier on the end; since we
     * take it off when reading input we can't really use them reliably
     * to direct outgoing messages yet (sorry guys!)
     *
     * Note that a bare domain can be a valid JID.
     *
     * @param string $jid string to check
     * @param bool $check_domain whether we should validate that domain...
     *
     * @return     boolean whether the string is a valid JID
     */
    protected function validateBaseJid($jid, $check_domain = false)
    {
        try {
            $parts = $this->splitJid($jid);
            if ($check_domain) {
                if (!$this->checkDomain($parts['domain'])) {
                    return false;
                }
            }
            // missing; empty isn't kosher
            return is_null($parts['resource']);
        } catch (UnexpectedValueException $e) {
            return false;
        }
    }

    /**
     * Splits a Jabber ID (JID) into node, domain, and resource portions.
     *
     * Based on validation routine submitted by:
     * @param string $jid string to check
     *
     * @return array with "node", "domain", and "resource" indices
     * @throws UnexpectedValueException if input is not valid
     * @license Licensed under ISC-L, which is compatible with everything else that keeps the copyright notice intact.
     *
     * @copyright 2009 Patrick Georgi <patrick@georgi-clan.de>
     */
    protected function splitJid($jid)
    {
        $chars = [];
        /* the following definitions come from stringprep, Appendix C,
           which is used in its entirety by nodeprop, Chapter 5, "Prohibited Output" */
        // C.1.1 Latin-1 space characters
        $chars['1.1'] = "\x{20}";
        // C.1.2 Non-Latin-1 space characters
        $chars['1.2'] = "\x{a0}\x{1680}\x{2000}-\x{200b}\x{202f}\x{205f}\x{3000a}";
        // C.2.1 Latin-1 control characters
        $chars['2.1'] = "\x{00}-\x{1f}\x{7f}";
        // C.2.2 Non-Latin-1 control characters
        $chars['2.2'] = "\x{80}-\x{9f}\x{6dd}\x{70f}\x{180e}\x{200c}\x{200d}"
             . "\x{2028}\x{2029}\x{2060}-\x{2063}\x{206a}-\x{206f}\x{feff}"
             . "\x{fff9}-\x{fffc}\x{1d173}-\x{1d17a}";
        // C.3 - Private Use
        $chars['3'] = "\x{e000}-\x{f8ff}\x{f0000}-\x{ffffd}\x{100000}-\x{10fffd}";
        // C.4 - Non-character code points
        $chars['4'] = "\x{fdd0}-\x{fdef}\x{fffe}\x{ffff}\x{1fffe}\x{1ffff}"
            . "\x{2fffe}\x{2ffff}\x{3fffe}\x{3ffff}\x{4fffe}\x{4ffff}\x{5fffe}"
            . "\x{5ffff}\x{6fffe}\x{6ffff}\x{7fffe}\x{7ffff}\x{8fffe}\x{8ffff}"
            . "\x{9fffe}\x{9ffff}\x{afffe}\x{affff}\x{bfffe}\x{bffff}\x{cfffe}"
            . "\x{cffff}\x{dfffe}\x{dffff}\x{efffe}\x{effff}\x{ffffe}\x{fffff}"
            . "\x{10fffe}\x{10ffff}";
        // C.5 - Surrogate codes
        // We can't use preg_match to check this, fix below
        // $chars['5'] = "\x{d800}-\x{dfff}";
        // C.6 - Inappropriate for plain text
        $chars['6'] = "\x{fff9}-\x{fffd}";
        // C.7 - Inappropriate for canonical representation
        $chars['7'] = "\x{2ff0}-\x{2ffb}";
        // C.8 - Change display properties or are deprecated
        $chars['8'] = "\x{340}\x{341}\x{200e}\x{200f}\x{202a}-\x{202e}\x{206a}-\x{206f}";
        // C.9 - Tagging characters
        $chars['9'] = "\x{e0001}\x{e0020}-\x{e007f}";

        $nodeprep_chars = implode('', $chars);
        // Nodeprep forbids some more characters
        $nodeprep_chars .= "\x{22}\x{26}\x{27}\x{2f}\x{3a}\x{3c}\x{3e}\x{40}";

        // Resourceprep forbids all from stringprep, Appendix C, except for C.1.1
        $resprep_chars = implode('', array_slice($chars, 1));

        $parts = explode('/', $jid, 2);
        if (count($parts) > 1) {
            $resource = $parts[1];
        // if ($resource == '') then
            // Warning: empty resource isn't legit.
            // But if we're normalizing, we may as well take it...
        } else {
            $resource = null;
        }

        $node = explode("@", $parts[0]);
        if ((count($node) > 2) || (count($node) == 0)) {
            // TRANS: Exception thrown when using too many @ signs in a Jabber ID.
            throw new UnexpectedValueException(_m('Invalid JID: too many @s.'));
        } elseif (count($node) == 1) {
            $domain = $node[0];
            $node = null;
        } else {
            $domain = $node[1];
            $node = $node[0];
            if ($node == '') {
                // TRANS: Exception thrown when using @ sign not followed by a Jabber ID.
                throw new UnexpectedValueException(_m('Invalid JID: @ but no node'));
            }
        }

        if (!is_null($node)) {
            // Length limits per https://xmpp.org/rfcs/rfc3920.html#addressing
            if (mb_strlen($node, '8bit') > 1023) {
                // TRANS: Exception thrown when using too long a Jabber ID (>1023).
                throw new UnexpectedValueException(_m('Invalid JID: node too long.'));
            }
            // C5 - Surrogate codes is ensured by encoding check
            if (preg_match("/[{$nodeprep_chars}]/u", $node)
                || mb_detect_encoding($node, 'UTF-8', true) !== 'UTF-8') {
                // TRANS: Exception thrown when using an invalid Jabber ID.
                // TRANS: %s is the invalid Jabber ID.
                throw new UnexpectedValueException(sprintf(_m('Invalid JID node "%s".'), $node));
            }
        }

        if (mb_strlen($domain, '8bit') > 1023) {
            // TRANS: Exception thrown when using too long a Jabber domain (>1023).
            throw new UnexpectedValueException(_m('Invalid JID: domain too long.'));
        }
        if (!common_valid_domain($domain)) {
            // TRANS: Exception thrown when using an invalid Jabber domain name.
            // TRANS: %s is the invalid domain name.
            throw new UnexpectedValueException(sprintf(_m('Invalid JID domain name "%s".'), $domain));
        }

        if (!is_null($resource)) {
            if (mb_strlen($resource, '8bit') > 1023) {
                // TRANS: Exception thrown when using too long a resource (>1023).
                throw new UnexpectedValueException('Invalid JID: resource too long.');
            }
            if (preg_match("/[{$resprep_chars}]/u", $resource)) {
                // TRANS: Exception thrown when using an invalid Jabber resource.
                // TRANS: %s is the invalid resource.
                throw new UnexpectedValueException(sprintf(_m('Invalid JID resource "%s".'), $resource));
            }
        }

        return array('node' => is_null($node) ? null : mb_strtolower($node),
            'domain' => is_null($domain) ? null : mb_strtolower($domain),
            'resource' => $resource);
    }

    /**
     * Check if this domain's got some legit DNS record
     * @param $domain
     * @return bool
     */
    protected function checkDomain($domain)
    {
        if (checkdnsrr("_xmpp-server._tcp." . $domain, "SRV")) {
            return true;
        }
        if (checkdnsrr($domain, "ANY")) {
            return true;
        }
        return false;
    }

    public function onStartImDaemonIoManagers(&$classes)
    {
        parent::onStartImDaemonIoManagers($classes);
        $classes[] = new XmppManager($this); // handles pings/reconnects
        return true;
    }

    public function sendMessage($screenname, $body)
    {
        $this->queuedConnection()->message($screenname, $body, 'chat');
    }

    /**
     * Build a queue-proxied XMPP interface object. Any outgoing messages
     * will be run back through us for enqueing rather than sent directly.
     *
     * @return QueuedXMPP
     * @throws Exception if server settings are invalid.
     */
    public function queuedConnection()
    {
        if (!isset($this->server)) {
            // TRANS: Exception thrown when the plugin configuration is incorrect.
            throw new Exception(_m('You must specify a server in the configuration.'));
        }
        if (!isset($this->port)) {
            // TRANS: Exception thrown when the plugin configuration is incorrect.
            throw new Exception(_m('You must specify a port in the configuration.'));
        }
        if (!isset($this->user)) {
            // TRANS: Exception thrown when the plugin configuration is incorrect.
            throw new Exception(_m('You must specify a user in the configuration.'));
        }
        if (!isset($this->password)) {
            // TRANS: Exception thrown when the plugin configuration is incorrect.
            throw new Exception(_m('You must specify a password in the configuration.'));
        }

        return new QueuedXMPP(
            $this,
            ($this->host ?: $this->server),
            $this->port,
            $this->user,
            $this->password,
            $this->resource,
            $this->server,
            ($this->debug ? true : false),
            ($this->debug ? \XMPPHP\Log::LEVEL_VERBOSE : null)
        );
    }

    public function sendNotice($screenname, Notice $notice)
    {
        try {
            $msg = $this->formatNotice($notice);
            $entry = $this->format_entry($notice);
        } catch (Exception $e) {
            common_log(LOG_ERR, __METHOD__ . ": Discarding outgoing stanza because of exception: {$e->getMessage()}");
            return false;   // return value of sendNotice is never actually used as of now
        }
        $this->queuedConnection()->message($screenname, $msg, 'chat', null, $entry);
        return true;
    }

    /**
     * extra information for XMPP messages, as defined by Twitter
     *
     * @param Notice $notice Notice being sent
     *
     * @return string Extra information (Atom, HTML, addresses) in string format
     */
    protected function format_entry(Notice $notice)
    {
        $profile = $notice->getProfile();

        $entry = $notice->asAtomEntry(true, true);

        $xs = new XMLStringer();
        $xs->elementStart('html', array('xmlns' => 'http://jabber.org/protocol/xhtml-im'));
        $xs->elementStart('body', array('xmlns' => 'http://www.w3.org/1999/xhtml'));
        $xs->element('a', array('href' => $profile->profileurl), $profile->nickname);
        try {
            $parent = $notice->getParent();
            $orig_profile = $parent->getProfile();
            $orig_profurl = $orig_profile->getUrl();
            $xs->text(" => ");
            $xs->element('a', array('href' => $orig_profurl), $orig_profile->nickname);
            $xs->text(": ");
        } catch (NoParentNoticeException $e) {
            $xs->text(": ");
        }
        // FIXME: Why do we replace \t with ''? is it just to make it pretty? shouldn't whitespace be handled well...?
        $xs->raw(str_replace("\t", "", $notice->getRendered()));
        $xs->text(" ");
        $xs->element(
            'a',
            [
                'href' => common_local_url(
                    'conversation',
                    ['id' => $notice->conversation]
                ) . '#notice-' . $notice->id,
            ],
            // TRANS: Link description to notice in conversation.
            // TRANS: %s is a notice ID.
            sprintf(_m('[%u]'), $notice->id)
        );
        $xs->elementEnd('body');
        $xs->elementEnd('html');

        $html = $xs->getString();

        return $html . ' ' . $entry;
    }

    public function receiveRawMessage($pl)
    {
        $from = $this->normalize($pl['from']);

        if (is_null($from)) {
            $this->log(LOG_WARNING, 'Ignoring message from invalid JID: ' . $pl['xml']->toString());
            return true;
        }

        if ($pl['type'] !== 'chat') {
            $this->log(LOG_WARNING, "Ignoring message of type " . $pl['type'] . " from $from: " . $pl['xml']->toString());
            return true;
        }

        if (mb_strlen($pl['body']) == 0) {
            $this->log(LOG_WARNING, "Ignoring message with empty body from $from: " . $pl['xml']->toString());
            return true;
        }

        $this->handleIncoming($from, $pl['body']);

        return true;
    }

    /**
     * Normalizes a Jabber ID for comparison, dropping the resource component if any.
     *
     * @param string $jid JID to check
     * @return string an equivalent JID in normalized (lowercase) form
     */
    public function normalize($jid)
    {
        try {
            $parts = $this->splitJid($jid);
            if (!is_null($parts['node'])) {
                return $parts['node'] . '@' . $parts['domain'];
            } else {
                return $parts['domain'];
            }
        } catch (UnexpectedValueException $e) {
            return null;
        }
    }

    /**
     * Add XMPP plugin daemon to the list of daemon to start
     *
     * @param array $daemons the list of daemons to run
     *
     * @return boolean hook return
     */
    public function onGetValidDaemons(&$daemons)
    {
        if (isset($this->server) &&
            isset($this->port) &&
            isset($this->user) &&
            isset($this->password)) {
            array_push(
                $daemons,
                INSTALLDIR . '/scripts/imdaemon.php'
            );
        }

        return true;
    }

    /**
     * Plugin Nodeinfo information
     *
     * @param array $protocols
     * @return bool hook true
     */
    public function onNodeInfoProtocols(array &$protocols)
    {
        $protocols[] = "xmpp";
        return true;
    }

    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = array('name' => 'XMPP',
            'version' => self::PLUGIN_VERSION,
            'author' => 'Craig Andrews, Evan Prodromou',
            'homepage' => GNUSOCIAL_ENGINE_REPO_URL . 'tree/master/plugins/XMPP',
            'rawdescription' =>
            // TRANS: Plugin description.
                _m('The XMPP plugin allows users to send and receive notices over the XMPP/Jabber network.'));
        return true;
    }

    /**
     * Checks whether a string is a syntactically valid Jabber ID (JID),
     * either with or without a resource.
     *
     * Note that a bare domain can be a valid JID.
     *
     * @param string $jid string to check
     * @param bool $check_domain whether we should validate that domain...
     *
     * @return     boolean whether the string is a valid JID
     */
    protected function validateFullJid($jid, $check_domain = false)
    {
        try {
            $parts = $this->splitJid($jid);
            if ($check_domain) {
                if (!$this->checkDomain($parts['domain'])) {
                    return false;
                }
            }
            return $parts['resource'] !== ''; // missing or present; empty ain't kosher
        } catch (UnexpectedValueException $e) {
            return false;
        }
    }
}
