Thank you for your interest in OpenSALT!

Do the checklist before filing an issue:

- [ ] Is this something you can **debug and fix**? Send a pull request after filing an issue! Bug fixes and documentation fixes are welcome.
- [ ] Have an idea for a feature? Post the feature request on [Github Issues](http://www.github.com/opensalt/opensalt/Issues).
- [ ] Label your issue as part of a larger epic such as "import/export", feature, bug, and *review* if you want us to see it more quickly

BUGS

Make sure to add **all the information needed to understand the bug** so that someone can help. If the info is missing we'll add the 'Needs more information' label and close the issue until there is enough information.

- [ ] Provide **Mockup Screenshots** where appropriate
- [ ] Steps taken and version of OpenSALT you are working on (from the main opensalt home page, select 'about')
- [ ] Operating System and Browser

GHERKIN:
USER STORY:

AS A user with access to create items
I WANT the legacy processes and workspace processes to create items in an identical manner
SO THAT duplicate items and gaps in items do not get generated

SAMPLE ACCEPTANCE CRITERIA:

1
GIVEN items need to be generated in Workspace or Legacy using a system-wide naming convention
WHEN the system generates an Item Name
THEN the Item Name must be unique (not previously used within the system)
