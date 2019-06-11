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

* `DOCUMENTATION/DEVELOPERS/CONTRIBUTING/coding_standards.md`: How your code should be structured and formatted to be
  accepted into the GNU social codebase.
* `/DOCUMENTATION/DEVELOPERS/CONTRIBUTING/merge_request_checklist.md`: A quick checklist to review before submission.


## Merge Request Process

1. Ensure you strip any trailing spaces off and checked the file with php-cs-fixer
2. Increase the version numbers in any examples files and the README.md to the new version that this
   Pull Request would represent. The versioning scheme we use is [SemVer](http://semver.org/).
3. You may merge the Pull Request in once you have the sign-off of two other developers, or if you
   do not have permission to do that, you may request the second reviewer to merge it for you.


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
