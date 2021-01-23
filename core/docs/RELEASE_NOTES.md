Release Notes
=============

2.2 ([Full issue and PR list](https://github.com/opensalt/opensalt/milestone/23?closed=1))
---
### New
* Copy Framework Feature
  * As a derivative framework
  * Or creating a derivative framework with exactMatch associations
* File System configuration for images or files 
* Ability to add additional (Non-CASE) fields to CfItem using /additionalfield as Super User
* Ability to define a Framework with a specific type
* Authentication configuration for the CASE Network (casenetwork.imsglobal.org)
* SEe docs.opensalt.org for full capabilities

### Improvements
- [Update Framework Tool by Spreadsheet can match with associations](http://docs.opensalt.org/en/latest/index.html#h10414a76521969321d1aa7b43555d12)
- Import Framework via Spreadsheet is back
- Ability to add a left side footer on frontpage UI

### Fixes
- Order on the UI and backend uses SequenceNumber
- CfDef pages secured


2.1.1 ([Full issue and PR list](https://github.com/opensalt/opensalt/issues?&q=milestone%3A2.1+is%3Aclosed))
---
### New

### Improvements

* Updated to Symfony 4.0 using the Symfony Flex directory structure
  * Moved to current recommended directory structure
    * All configuration has been moved to config/
    * Twig templates have been moved to templates/
    * JS and SASS assets have been moved to assets/

### Fixes



2.1 ([Full issue and PR list](https://github.com/opensalt/opensalt/milestone/18?closed=1))
---
### New

### Improvements

* Updated to Symfony 3.4
  * Moved to current recommended directory structure
    * All bundles have been removed from src/
    * Moved all code to be under the App\ namespace (located at src/)
    * Twig templates have been moved to app/Resources/views

### Fixes



2.0 ([Full issue and PR list](https://github.com/opensalt/opensalt/issues?&q=milestone%3A2.0+is%3Aclosed))
---
### New

* The code has been migrated to a command-handler model
  * All changes to the database are done via a handler and there is only
    one place where a commit to the database occurs.
  * Changes are now being logged for audit purposes.

* Items and framework documents are now locked while being edited

* Added optional feature to display notifications of changes to others
  editing the same framework.

* In the framework view, the width of the tree and the detail information
  can be adjusted by moving the centre bar.

* Added the ability to export comments.


### Improvements

* Abbreviated statement can now be 60 characters instead of 50.

* Removed table listing organization admins from the login page.

* Frameworks with references to many other frameworks will now load the
  other framework information better.

* Added parsing for underline in markdown to show text as underlined
  instead of emphasized.


### Fixes

* Various fixes and improvements to importing frameworks.

* Various UI fixes and improvements.



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
