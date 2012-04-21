Modyllic is an SQL parser and set of schema management tools, written in PHP 5.2.

Modyllic is a collection of handy utilities for managing the schemas
and contents of SQL databases at an enterprise level of
sophistication.  By solving common problems a way traditional tools
don't, it is intended to make the model in your model-view-controller
framework...idyllic.  Modyllic is written in PHP, but can be useful as
a toolset for any language.

There have been a number of similar projects over the years, see our [[Prior Art]]
section for the list of ones we're currently aware of.

Modyliic supports MySQL's DDL commands for creating databases,
tables, views, procedures, functions, events and triggers.  A full list of
the SQL we know how to parse is available in the
[[dialect documentation | Dialect]].  There are also some of Modyllic's
features that are supported by [[extensions to SQL | Extensions]].

Three primary use cases are:

* Detecting and examining differences between databases. See [[sqldiff]] for
  details.

* Replacing conventional linear up/down scripts with more flexible
  change management. See [[sqldump]], [[sqldrop]], [[sqldiff]] and
  [[migrate]] for details.

* Providing a consistant, safe and transparent access to stored routines.
  See [[sqltophp]] for details.

And finally, incidentally, we provide a fast syntax highlighter based on
Modyllic's tokenizer in the form of [[sqlcolorize]].

Modyllic operates on [[schemas]], which can represent database, table, and
column definitions, routines, events, triggers, as well as data.  They are
stored in files as the SQL statements that would normally be used to create
the tables.

We also have some notes on doing a [[Parser Refactor]], a
[[Dialect Refactor]], and adding support for [[Schema Change Events]].
