# GNU social 1.3.x
(c) 2010 Free Software Foundation, Inc

This is the README file for GNU social, the free
software social networking platform. It includes
general information about the software and the
project.

The file INSTALL.md has useful instructions on how to
install this software.

System administrators may find the DOCUMENTATION/SYSTEM_ADMINISTRATORS
directory useful, namely:

- upgrade_from: upgrading from different software
- CONFIGURE.md: configuration options in gruesome detail.
- PLUGINS.md: how to install and configure plugins.

Developers may find the DOCUMENTATION/DEVELOPERS directory useful.

## About

GNU social is a free social networking
platform. It helps people in a community, company
or group to exchange short status updates, do
polls, announce events, or other social activities
(and you can add more!). Users can choose which
people to "follow" and receive only their friends'
or colleagues' status messages. It provides a
similar service to proprietary social network sites,
but is much more awesome.

With a little work, status messages can be sent to
mobile phones, instant messenger programs (using
XMPP), and specially-designed desktop clients that
support the Twitter API.

GNU social supports open standards (such as OStatus
<https://www.w3.org/community/ostatus/>) that lets users in
different networks follow each other. It enables a
distributed social network spread all across the
Web.

GNU social was originally developed as "StatusNet" by
StatusNet, Inc. with Evan Prodromou as lead developer.

It is shared with you in hope that you too make an
service available to your users. To learn more,
please see the Open Software Service Definition
1.1: <http://www.opendefinition.org/ossd>

### License

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public
License along with this program, in the file "COPYING".  If not, see
<http://www.gnu.org/licenses/>.

    IMPORTANT NOTE: The GNU Affero General Public License (AGPL) has
    *different requirements* from the "regular" GPL. In particular, if
    you make modifications to the GNU social source code on your server,
    you *MUST MAKE AVAILABLE* the modified version of the source code
    to your users under the same license. This is a legal requirement
    of using the software, and if you do not wish to share your
    modifications, *YOU MAY NOT INSTALL GNU SOCIAL*.

Documentation in the /doc-src/ directory is available under the
Creative Commons Attribution 3.0 Unported license, with attribution to
"GNU social". See <http://creativecommons.org/licenses/by/3.0/> for details.

CSS and images in the /theme/ directory are available under the
Creative Commons Attribution 3.0 Unported license, with attribution to
"GNU social". See <http://creativecommons.org/licenses/by/3.0/> for details.

Our understanding and intention is that if you add your own theme that
uses only CSS and images, those files are not subject to the copyleft
requirements of the Affero General Public License 3.0. See
<http://wordpress.org/news/2009/07/themes-are-gpl-too/>. This is not
legal advice; consult your lawyer.

Additional library software has been made available in the 'extlib'
directory. All of it is Free Software and can be distributed under
liberal terms, but those terms may differ in detail from the AGPL's
particulars. See each package's license file in the extlib directory
for additional terms.

Refer to COPYING.md for full text of the software license..

### Troubleshooting

The primary output for GNU social is syslog,
unless you configured a separate logfile. This is
probably the first place to look if you're getting
weird behaviour from GNU social.

If you're tracking the unstable version of
GNU social in the git repository (see below), and you
get a compilation error ("unexpected T_STRING") in
the browser, check to see that you don't have any
conflicts in your code.

### Unstable version

If you're adventurous or impatient, you may want
to install the development version of GNU social.
To get it, use the git version control tool
<http://git-scm.com/> like so:

    git clone git@notabug.org:diogo/gnu-social.git

In the current phase of development it is probably
recommended to use git as a means to stay up to date
with the source code. You can choose between these
branches:
- master    "stable", usually working well
- nightly   "unstable", most updates, not always working as expected

To keep it up-to-date, use 'git pull'. Watch for conflicts!

## Further information

There are several ways to get more information about GNU social.

* The #social IRC channel on freenode.net <https://www.freenode.net/>.
* The unofficial XMPP room linked to IRC on <xmpp:gnusocial@conference.bka.li>
* The GNU social website <https://gnu.io/social/>

* GNU social has a bug tracker for any defects you may find, or ideas for
  making things better. <https://notabug.org/diogo/gnu-social/issues>
* Patches are welcome, preferrably to our repository on notabug.org. <https://notabug.org/diogo/gnu-social>
