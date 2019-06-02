# Contributing to GNU social

First of all, if you're reading this intending to contribute to GNU social,
thanks! Free software development only happens when people like you take an
interest in giving back to the software they themselves use, and their
community.

When contributing to this repository, please first discuss the change you wish to
make via issue, email, or any other method with the owners of this repository before
making a change.

There's a few files you should read before going forward with a merge request
or a patch submission.  They detail what this file touches on in brief.  They
are:

* coding_standards.md: How your code should be structured and formatted to be
  accepted into the GNU social codebase.
* merge_request_checklist.md: A quick checklist to review before submission.

## Merge Request Process

1. Ensure you strip any trailing spaces off and checked the file with php-cs-fixer
2. Increase the version numbers in any examples files and the README.md to the new version that this
   Pull Request would represent. The versioning scheme we use is [SemVer](http://semver.org/).
3. You may merge the Pull Request in once you have the sign-off of two other developers, or if you
   do not have permission to do that, you may request the second reviewer to merge it for you.


## Code of Conduct

### Our Pledge

In the interest of fostering an open and welcoming environment, we as
contributors and maintainers pledge to making participation in our project and
our community a harassment-free experience for everyone, regardless of age, body
size, disability, ethnicity, gender identity and expression, level of experience,
nationality, personal appearance, race, religion, or sexual identity and
orientation.

### Our Standards

Examples of behavior that contributes to creating a positive environment
include:

* Using welcoming and inclusive language
* Being respectful of differing viewpoints and experiences
* Gracefully accepting constructive criticism
* Focusing on what is best for the community
* Showing empathy towards other community members

Examples of unacceptable behavior by participants include:

* The use of sexualized language or imagery and unwelcome sexual attention or
advances
* Trolling, insulting/derogatory comments, and personal or political attacks
* Public or private harassment
* Publishing others' private information, such as a physical or electronic
  address, without explicit permission
* Other conduct which could reasonably be considered inappropriate in a
  professional setting

### Our Responsibilities

Project maintainers are responsible for clarifying the standards of acceptable
behavior and are expected to take appropriate and fair corrective action in
response to any instances of unacceptable behavior.

Project maintainers have the right and responsibility to remove, edit, or
reject comments, commits, code, wiki edits, issues, and other contributions
that are not aligned to this Code of Conduct, or to ban temporarily or
permanently any contributor for other behaviors that they deem inappropriate,
threatening, offensive, or harmful.

### Scope

This Code of Conduct applies both within project spaces and in public spaces
when an individual is representing the project or its community. Examples of
representing a project or community include using an official project e-mail
address, posting via an official social media account, or acting as an appointed
representative at an online or offline event. Representation of a project may be
further defined and clarified by project maintainers.

### Enforcement

Instances of abusive, harassing, or otherwise unacceptable behavior may be
reported by contacting the project team at [INSERT EMAIL ADDRESS]. All
complaints will be reviewed and investigated and will result in a response that
is deemed necessary and appropriate to the circumstances. The project team is
obligated to maintain confidentiality with regard to the reporter of an incident.
Further details of specific enforcement policies may be posted separately.

Project maintainers who do not follow or enforce the Code of Conduct in good
faith may face temporary or permanent repercussions as determined by other
members of the project's leadership.

### Attribution

This Code of Conduct is adapted from the [Contributor Covenant][homepage], version 1.4,
available at [http://contributor-covenant.org/version/1/4][version]

[homepage]: http://contributor-covenant.org
[version]: http://contributor-covenant.org/version/1/4/


## The Code of Conflict

GNU social has a high submission standard and we want to keep quality code in the
codebase and bad code out of it. As such your code will be closely scrutinized,
and you might take this criticism personally. Please understand that this is
meant to keep the standards of the codebase up, and isn't meant personally. All
the same, this isn't an excuse for poor behaviour, and a reviewer shouldn't be
misbehaving towards submitters. The Code of Conflict outlines the dispute
resolution mechanism if something does come up, so give it a read.


## Coding Standards

Since we will be expected to maintain your code once it's submitted, we ask you
to adhere to certain coding standards that make it easier for us to do so. If
code doesn't follow them, it will be rejected, so please read up on these.


## Bug Reports

Please report bugs to the issue tracker at
<https://notabug.org/diogo/gnu-social/issues> Avoid assigning the labels
yourself, as these are for the development team to assign priority and area of
coverage to a subject. Please only submit something here if you are certain it
is a bug or represents a feature enhancement that we do not presently have. If
you are uncertain whether it's a bug, please feel free to ask
at #social IRC channel on freenode.net https://www.freenode.net/.

When reporting a bug, please try to include as much information as possible,
including the environment being run on (if it's a common LAMP stack just give
us version numbers of the main stack components, that's fine), and the specific
error you get. If you do not get a client-facing error, please check the PHP
error_log and ensure there isn't something silently reported there, as well as
the GNU social log. Try to include steps to reproduce the error as well, as if
we cannot reproduce the error, we can't fix it!

It is perfectly acceptable to reference the archive page of a discussion on the
mailing list for the bug report, by the way, as long as it includes all the
information we need for a bug report.


## Submitting Feature Requests / Enhancement Requests

Social media is constantly evolving, and we welcome ideas about how we can
change and evolve GNU social to keep it the excellent piece of software that it
is. However, there are a few things we ask you do when submitting feature
requests:

1. Understand that since we have a limited amount of developers and these people
contribute in their free time, we may prioritize things differently than you
value them. Oftentimes this is because certain requests involve less changes
to the existing codebase than others, and therefore this makes them easier
to add.
2. Please search the existing feature requests and enhancements to see if a
similar request exists. If one does but you have different ideas about how
to do it or what it should entail, please add a comment to the existing idea
rather than create a new one for your "version" of it. Duplicate submissions
mean we spend more time maintaining the tracker and less time actually
working on the codebase!
3. When outlining the way that you see something working, don't be afraid to be
as detailed as possible! We may not implement it exactly as you describe for
any variety of reasons, but the more concrete and fleshed out an idea is, the
easier it is for us to know what you want and be able to implement it in a
sane and secure fashion.
4. When describing a possible new idea and its mechanisms of operation, the key
words "MUST", "MUST NOT", "REQUIRED", "SHALL", "SHALL NOT", "SHOULD",
"SHOULD NOT", "RECOMMENDED", "MAY", and "OPTIONAL" in the issue submission
are to be interpreted as described in RFC 2119.
<https://tools.ietf.org/html/rfc2119>

Finally, and just as a call back to the first point, realize just because we
might not rush to implement something, doesn't mean that we don't want to
implement it! We would rather take the time to do something right the first
time, then hurriedly apply a new idea, or a fix, only to have to patch it later.


## Branch of Code Submissions

Unless you've been specifically directed otherwise, all submissions of code
should be against the `nightly` branch, so make sure any modifications are based
on Nightly.


## Copyright / Licensing

You acknowledge that by submitting code to GNU social, you are licensing it under
the GNU AGPLv3 unless there is an extenuating circumstance where it would be
licensed differently (such as modifications to an external library we include
such as Stomp).

You also acknowledge that unless you assign a copyright explicitly, it will be
assumed to be assigned to GNU social.

Thanks for considering submission, and happy hacking!
