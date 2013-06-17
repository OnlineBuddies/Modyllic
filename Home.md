Modyllic is an SQL parser and set of schema management tools, written in PHP 5.2, and
useful for anyone working with SQL.

Modyllic is at its core, a tool for managing schema changes across branches.  Because it allows you to store your schema as DDL, it means that merges between branches are easily handled using standard tools.  In addition to analysis (dump and diff) tools, it also provides migration tools (migrate, apply).  It also provides tools for making working with stored procedures in PHP much easier-- it comes with a tool for generating a PHP wrapper for calling your stored procedures that include type checking and fully inflated return values, eg, you can return a table and get an array containing all of the rows without a separate fetch step.

There have been a number of similar projects over the years, see our [[Prior Art]]
section for the list of ones we're currently aware of.

Modyllic supports MySQL's DDL commands for creating databases,
tables, views, procedures, functions, events and triggers.  A full list of
the SQL we know how to parse is available in the
[[dialect documentation | Dialect]].  There are also some of Modyllic's
features that are supported by [[extensions to SQL | Extensions]].

Three primary use cases are:

* Detecting and examining differences between databases. See [[modyllic diff]] for
  details.

* Replacing conventional linear up/down scripts with more flexible
  change management. See [[modyllic dump]], [[modyllic drop]], [[modyllic diff]] and
  [[modyllic migrate]] for details.

* Providing a consistant, safe and transparent access to stored routines.
  See [[modyllic procstophp]] for details.

And finally, incidentally, we provide a fast syntax highlighter based on
Modyllic's tokenizer in the form of [[modyllic colorize]].

Modyllic operates on [[schemas]], which can represent database, table, and
column definitions, routines, events, triggers, as well as data.  They are
stored in files as the SQL statements that would normally be used to create
the tables.

We also have some notes on doing a [[Parser Refactor]], a
[[Dialect Refactor]], and adding support for [[Schema Change Events]].

Other documentation is included in the distribution itself:

* Documentation on how to [[publish new versions | Publishing]]
* Some slightly out of date documentation on [[internals and how you might extend it | Hacking]]