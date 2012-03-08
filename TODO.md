Warnings and errors:

  * Creating a column with the same name as an existing alias
  * A view and a table that share a name

Bug? Changing a proc argument list sets the ->from attribute, was producing
a weird looking result.

Add ability to run diff in "incomplete" mode, which won't drop missing
tables or procs.  This lets you update a subset of the schema very
quickly, which will be handy in development if the schema is split up
into multiple files.

  * Concept of expansion and contraction migration modes:
  *     http://exortech.com/blog/2009/02/01/weekly-release-blog-11-zero-downtime-database-deployment/
  * Migration tool level support for migration versions that are only applied when the new version is fully applied.
  * --partial is alias for --no-drop-tables --no-drop-procs
  * Also has: --no-drop-cols
  * And --no-drop is an alias for --no-drop-tables --no-drop-procs and --no-drop-cols
  * Have --no-slow which will avoid any schema changes likely to be
    slow-- I think this is changing columns and adding indexes.
  * Have --no-procs and --no-tables options to skip generating proc and
    table changes.

Optionally push static tables into static PHP classes? Maybe...

Refactoring concerns:

  * Rename PlainSQL to something like Bare or NoMeta
  * Rename StrictSQL to something like Assert

Documentation concerns:

  * List dialects in help
  * Describe schema specifiers

Important completeness concerns:

  * Support ASC/DESC index flags

Generator features/cleanup:

  * Make generator fold expansion of UNIQUE columns back into UNIQUE column attr.
  * Make generator fold expansion of SERIAL columns back into SERIAL.

Wishlist Items:

  * Column order only changes, maybe
