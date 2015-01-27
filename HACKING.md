Hacking
=======

In case you want to contribute to silex-jira-oauth-provider, here are some
basic rules to follow.

Patch Style
-----------

We basically only have two rules on patches. A patch ...

* ... contains a small and atomic change
* ... has an good commit message

*Small and atomic commits*

Please keep patches small and concise. This helps making reviews fast and easy
and with the addition of good commit messages we create up-to-date documentation
on the fly.

*Good commit messages*

We are using the 50/80 rule on commit messages.

Meaning that a *commit message* has a subject line with a maximum of 50
characters. Followed by a blank line is a detailed description where each line
is 80 characters long at max.

The *subject line* contains of a prefix and a short summary in passive present
tense language. The prefix can be inferred by the following rules:

* Changes on top level files do not need a prefix
* Changes in the src folder have a prefix that equals the name of the
  subfolder

For example a change in the oauth folder:

> oauth: Add passing of a redirect route

The *description* explains the content of the commit. This usually contains

* the problem or misbehavior
* the solution
* additional information

Please do not assume that the code is self-documenting, no matter how good it
is. If you need inspiration just browse the git log for examples.

Coding Style
------------

We use several different languages in this project and we do not have
sophisticated rules for managing them. Instead we encourage you to apply some
common sense when it comes to naming classes, methods and variables.

Additionally we have some general formatting rules. The main goal of these rules
is to increase readability of the overall code.

* Use unix line endings (CR, \n).
* For indentions use tabs in the size of 4 spaces instead of only spaces.
* Remove unnecessary white spaces. You can spot them in gerrit and also in git
  diff with the following setting
  (git config --global color.diff.whitespace "red reverse")
* Wrap lines and strings after 80 characters. This rule is relaxed only if
  readability is significantly decreased.
* Always use curly braces - even for single line if statements. Keep the opening
  brace in the same line as the statement.