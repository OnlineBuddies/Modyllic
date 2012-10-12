Revision history for PHP module Modyllic

v0.2.9 2012-10-12

Changes:

* Make the build fail if pirum can't actually be run 
* Add geometric types 
* Add more build requirements 
* Refactor metadata into a normal static table 
* Rename SQLMETA to MODYLLIC 
* Switch to creating the SQLMETA table via a schema object rather then by hand 
* Fix database creation during migration 
* Set indexes associated with primary key columns as column defined, which saves us from having to have a special case is_primary check later on. 
* Make it easy to override the fetch type 

Fixes:

* Fix #191 -- Emit BIGINTs for SERIAL columns 
* Fix #207: metatable generation during migrates

v0.2.8 2012-09-27

* Add support for extending your PHP include path for Modyllic's commandline
  tools with the MODYLLIC_LIB_PATH environment variable.

v0.2.7 2012-09-27

* Add deprecation warnings for `migrate`, `sqlcolorize`, `sqldiff`, `sqldrop`,
  `sqldump`, `sqlpreparse`, `sqlprofile`, `sqltophp`.  Use the `modyllic` command
  from now on.
* Removed previously deprecated dialect names
* Fix handling of multibyte strings

v0.2.6 2012-09-20

* Fix bug: We remove metadata with delete_meta not drop_meta

v0.2.5 2012-09-19

* Add DateTime assertion tests and correct DateTime assertions
* Fix metadata generation for routine attributes
* Fix assertion on constant variable name
* Improve integer assertions
* A variety of release related fixes

v0.2.4 2012-08-09

* Only wrap general errors for procs that we fetch the results on
* Fix colorize to make it handle commandline arguments in a standard way--
  the move to a unified modyllic commandline had broken it.
* Add an exception->error handler and made using the CommandLine class load
  it and the autoloader
* Fix case undefined indexes involving aliases (#186)
* Make boolean (and serial) persistent metadata in MySQL

v0.2.3 2012-07-30

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

v0.2.2 2012-05-17

* Add aliases for the old dialect names and warnings if you use them
* Fix method signature mismatches and bogus defaults
* Correct type hints from bulk type hint change

v0.2.1 2012-05-08

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

v0.2.0 2012-04-30

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

v0.1.7 2012-04-13

* No changelog for this version.

v0.1.6 2012-03-30

* No changelog for this version.

v0.1.5 2012-03-19

* No changelog for this version.

v0.1.4 2012-03-06

* No changelog for this version.

v0.1.3 2012-03-06

* No changelog for this version.

v0.1.2 2012-02-07

* No changelog for this version.

v0.1.1 2012-01-31

* No changelog for this version.

v0.1.0 2012-01-31

* No changelog for this version.

