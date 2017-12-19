Release Notes
=============

1.3
---
User Guide corresponding to 1.3
https://docs.google.com/document/d/1AtSvwpxcVABon2QzKzGhcHg4KV54JbpKnaIsDXKVkhk/edit?usp=sharing 
### New

* Markdown editor
  * Full statements, abbreviated statements, and notes now support Markdown
    syntax and embedded LaTeX math.
    * Removed support of HTML except for ol, ul, li, b, i, u, br, and p tags


### Improvements

* Added CORS headers to API returns
  * Allow the API to be used in a XSS way from browsers so that the API can
    be used by other applications

* Now using Chrome to run automated tests instead of PhantomJS
  * PhantomJS is no longer supported now that Chrome-headless is available

* Added additional automated tests
  * Over 125 automated tests now exist and are run against all pull requests


### Fixes

* Fix ordering issues
  * When importing from a spreadsheet treat the human coding scheme components
    independently and compare numerically when applicable (i.e. 10 > 2)
  * Use the sequence number from associations as the primary sort method

* Support larger UTF8 character set
  * Fix support for non-Basic Multilingual Plane UTF8 characters

* Fix deleting comments
  * Allow comments to be deleted when upvoted or replied to

* Fix adding/editing item type of items
  * The widget used would sometimes display in odd places

* Remove unavailable buttons
  * Remove create/import framework when a user does not have edit rights
  * Remove edit buttons when a framework is "Adopted"
