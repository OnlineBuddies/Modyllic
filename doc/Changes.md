[Release History](https://github.com/OnlineBuddies/Modyllic/releases) for PHP module Modyllic

[v0.2.26](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.2.26) 2014-02-18

* Nope, now that we're properly merging errors, we don't actually want to emit them at preparse time

[v0.2.25](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.2.25) 2014-02-18

* Errors should be propagated when merging parsed schema, eg, loading preparse files
* Fail preparsing when errors are detected
* Add links to the release history to the generated changelog

[v0.2.24](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.2.24) 2014-02-04

* Major speed improvements!
  * Minor speedup to is_delimiter (Aria Stewart)
  * Guard is_reserved with a cheap check, killing a huge amount of time (Aria Stewart)
  * Speed up is_num token check (Aria Stewart)
  * Speed up is_whitespace token check (Aria Stewart)
  * Speed up delimiter token check (Aria Stewart)
  * Actually index our table data =D (Rebecca Turner)
* Refactor the upgrading of a MODYLLIC table in the DB to a MetaTable schema object (Rebecca Turner)

[v0.2.23](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.2.23) 2014-01-06

* Static tables with null values will now compare correctly
* Comparing normalized types will now always use the right type while normalizing
* Add ability to issue alters individually for, eg, replicating 5.5->5.6
* Make attempts to insert into tables that don't yet exist non fatal-- although the insert WILL fail
* Add facility for determining non-reserved keywords for highlighting purposes
* Only emit ROW_FORMAT when it differs from the default.
* Stop emitting IF EXISTS in ModyllicSQL
* Use exec instead of prepare/execute to bypass bind param checking
* Add examples to readme

[v0.2.22](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.2.22) 2013-11-27

* Make unknown metadata an error rather than an exception
* Remove SQLMETA backwards compatibility
* Allow enabling or disabling an event through ALTER EVENT
* Upgrade substrs and matches to UTF-8
* Support for SQL expressions in INSERT commands

[v0.2.21](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.2.21) 2013-10-28

* Match the function signature up with Modyllic_Schema_Index_Foreign::validate
* Drop constraints, THEN indexes, THEN columns

[v0.2.20](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.2.20) 2013-10-22

* Fix emitting of altered events

[v0.2.19](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.2.19) 2013-10-18

* Fix false event diffs Events are tricky to handle right, for two reasons:
* Whenever possible, produce error messages rather than exceptions
* Add schema validation as a feature
* Work around MySQL constraint limitations, separating drops from adds.
* Fix handling of marking a column as a primary key-- columns are then marked not null by implication
* Fix false view diffs MySQL compresses the spaces between the AS keyword and the select statement, so we need to parse at least that far.
* Stop rewriting code body blocks when we didn't need to
* Fix guard against events not existing
* Fix #249: Guard against events not existing in MySQL 5.0

[v0.2.18](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.2.18) 2013-06-24

* Remove extra whitespace from view and event parsing (Rebecca Turner)
* Make all db interactions use UTF8 with MySQL (Rebecca Turner)

[v0.2.17](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.2.17) 2013-06-24

* Support default lengths varying based on signedness (Rebecca Turner)
* Merge "modyllic patch" and "modyllic apply" (Rebecca Turner)
* Allow defaults to be functions/constants, eg, true/false (Rebecca Turner)
* Add support for CREATE INDEX (Rebecca Turner)

[v0.2.16](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.2.16) 2013-05-29

* Actually connect to the database during apply (Aria Stewart)

[v0.2.15](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.2.15) 2013-05-29

* Add modyllic-apply command (Aria Stewart)

[v0.2.14](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.2.14) 2013-05-29

* Fix Modyllic view diff generation -- was referencing a changeset class rather then expected schema object (Rebecca Turner)

[v0.2.13](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.2.13) 2013-05-29

* Remove deprecated commandline scripts (Rebecca Turner)
* Add new Modyllic command for applying previously generated diffs to a database (Rebecca Turner)
* Support schema prefixed view names (Rebecca Turner)
* Add support for views/events/triggers to the MySQL loader (Rebecca Turner)
* Add support for the mysql ROW_FORMAT table option (Aria Stewart)
* Fix PHP5.3ism (Rebecca Turner)
* Substantially improve delimiter related error handling (Rebecca Turner)
* Make syntax errors consume a single character as an error token (Rebecca Turner)
* Improve colors used to highlight errors (Rebecca Turner)
* Fix commandline summary (Rebecca Turner)
* Improve the 'no MODYLLIC table' situation.  Works with DBs now, but still not dumps that lack it. (Rebecca Turner)
* Fix #192: Adding multiple columns resulted in invalid AFTER clauses (Rebecca Turner)

[v0.2.12](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.2.12) 2012-11-19

Bug fixes:

* Fix version order in changelogs
* Fix bug around sorting versions now that we're > *.10.0
* Fix difference detection bug

[v0.2.11](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.2.11) 2012-11-09

* Fix warning in some 5.3/5.4 configurations

[v0.2.10](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.2.10) 2012-11-09

Changes:

* Warn rather then crash if a database doesn't exist
* Clear progress bars in verbose mode only
* Stop magicing separate primary key delcarations into column-wise ones
* Suppress warnings on 5.4 due to PEAR maintained modules

Bug fixes:

* Fix bug in determining differences
* Fix migration from SQLMETA to MODYLLIC
* Fix 5.2 compatibility issues
* Fix 'HASH' type reserved word
* Fix #218- Cleans up metadata table handling.
* Fix column returns on empty sets, for real (#56)

[v0.2.9](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.2.9) 2012-10-12

Changes:

* Make the release fail if pirum or openssl can't actually be run 
* Add geometric types 
* Add more build requirements 
* Refactor metadata into a normal static table 
* Rename SQLMETA to MODYLLIC 
* Switch to creating the SQLMETA table via a schema object rather then by hand 
* Fix database creation during migration 
* Set indexes associated with primary key columns as column defined, which saves us from having to have a special case is_primary check later on. 
* Make it easy to override the fetch type 

Fixes:

* Fix #207: metatable generation during migrates 
* Fix #191 -- Emit BIGINTs for SERIAL columns

[v0.2.8](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.2.8) 2012-09-27

* Add support for extending your PHP include path for Modyllic's commandline
  tools with the MODYLLIC_LIB_PATH environment variable.

[v0.2.7](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.2.7) 2012-09-27

* Add deprecation warnings for `migrate`, `sqlcolorize`, `sqldiff`, `sqldrop`,
  `sqldump`, `sqlpreparse`, `sqlprofile`, `sqltophp`.  Use the `modyllic` command
  from now on.
* Removed previously deprecated dialect names
* Fix handling of multibyte strings

[v0.2.6](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.2.6) 2012-09-20

* Fix bug: We remove metadata with delete_meta not drop_meta

[v0.2.5](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.2.5) 2012-09-19

* Add DateTime assertion tests and correct DateTime assertions
* Fix metadata generation for routine attributes
* Fix assertion on constant variable name
* Improve integer assertions
* A variety of release related fixes

[v0.2.4](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.2.4) 2012-08-09

* Only wrap general errors for procs that we fetch the results on
* Fix colorize to make it handle commandline arguments in a standard way--
  the move to a unified modyllic commandline had broken it.
* Add an exception->error handler and made using the CommandLine class load
  it and the autoloader
* Fix case undefined indexes involving aliases (#186)
* Make boolean (and serial) persistent metadata in MySQL

[v0.2.3](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.2.3) 2012-07-30

* Fully automate the release process
* Move the tokenizer unit test into the unit tests directory
* Fix formatting of routine_args_type
* Fix formatting of proc_return_type and routine_args_type docs
* Give modyllic a single commandline interface
* Comments in tables can now attach to columns and the table
* Make tokens stringify to their debug representation and take advantage
* Fix normalization for dates and years the way we did for nums
* Fix #172 Non-token numification always set the value to 0
* Fix bug where data updates would get lost if a tables static status did not change
* Add coverage tools, a bunch of new unit tests, organize tests better.

[v0.2.2](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.2.2) 2012-05-17

* Add aliases for the old dialect names and warnings if you use them
* Fix method signature mismatches and bogus defaults
* Correct type hints from bulk type hint change

[v0.2.1](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.2.1) 2012-05-08

* Call CREATE TABLE correctly for SQLMETA
* Full support for 5.2-5.4 testing via Travis-CI
* Switch to using an auto loader
  NOTE: This means that any commandline tools will need to be updated to
  load the autoloader.
* Lots of refactoring to support the autoloader
* Strictness related cleanup:
  * Declare our abstract classes
  * Add private constructors to static-only classes
* Add type hints to our methods (#140)
* Fix #109, allow index comparisions able to be aware of column name aliases.
* Fix #33-- binary types never emit charset data

[v0.2.0](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.2.0) 2012-04-30

* Rework our package build process to be more palatable
* Rename schema classes to be Modyllic_Schema_*
* Move more documentation into the wiki and update the publishing document (Aria Stewart)
* Rename SQL generator dialects to be more useful
* Rename Modyllic_Commandline->Modyllic_CommandLine to match Console_CommandLine
* Add support for --version to all of the commandline tools
* Remove unused sqlmeta_exists from changeset support
* Remove now unused schema level sqlmeta tracking
* #103 Converting from static to non-static no longer results in deleted rows
* Fix strict error-- array_shift must take a variable as an argument
* #85 Recursive scan directories for .sql files
* Fix #110 - In sqlcolorize, on exit reset colors rather then explicitly setting white

[v0.1.2](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.1.2) 2012-04-26

* Packaging updates to use our own channel and document releases
* Class rename bug in SQL generators
* Loader fix required for static tables, spread across multiple files

[v0.1.3](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.1.3) 2012-04-26

* Remove unused method Modyllic_Loader::from_db
* Correct DSN loading to allow equals signs in values.
* URL decode DSN values prior to using them.
* Change terminal detection to only run tput if it can plausibly work.

[v0.1.4](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.1.4) 2012-04-26

* Add a debug flag to aid in debugging parser errors
* Remove verbose output from debugging
* Fix innumerable problems with migrate since the commandline refactor
* Allow colons or semicolons in dsns, to make command lines less painful
* Fix bug where the database name wasn't being emitted in some circumstances
* SQLMETA updates were bogus, changed to just delete and insert. 
* The static property on tables wasn't being emitted correctly.
* Fix bugs in primary key handling caused by support for key lengths
* Fix bug in how dynamically named indexes were emitted. Now use the name for the purposes of diffs but don't emit it
* Fix the file roles

[v0.1.5](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.1.5) 2012-04-26

* Add IF EXISTS to events. Fixes #93.
* Stop using 5.3 Exception form and just rethrow non-general errors
* RETURNS COLUMN assertions should allow empty result sets (#56)
* Implement support for extended inserts (#88)
* Fix missing is_primary attribute on columns
* Improve error messages for invalid delimiters (#82)

[v0.1.6](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.1.6) 2012-04-26

* Stop Modyllic_Parser::partial from having a return value
* Complain if no toschema is provided to sqldiff
* Fix a --progress divide by zero when the source file was empty
* Fix bug where a DROP DATABASE would ignore any following CREATEs
* Make the SQL generator able to only output specific kinds of data
* Boolean types were being treated as exactly equivalent to TINYINT and this isn't actually the case
* displayError should really be a static function
* Fix migration --create option
* Fix bugs around when SQLMETA is created
* Add support for MySQL Triggers
* Fix view changeset handling

[v0.1.7](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.1.7) 2012-04-26

* Add IF EXISTS to all of our DROPs
* Fix bugs in --only support
* Fix bugs in tracking sqlmeta_exists

[v0.1.1](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.1.1) 2012-01-31

* No changelog for this version.

[v0.1.0](https://github.com/OnlineBuddies/Modyllic/releases/tag/v0.1.0) 2012-01-31

* No changelog for this version.

