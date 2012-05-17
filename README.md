Modyllic is a collection of handy utilities for managing the schemas
and contents of SQL databases at an enterprise level of
sophistication.  By solving common problems a way traditional tools
don't, it is intended to make the model in your model-view-controller
framework...idyllic.  Modyllic is written in PHP, but can be useful as
a toolset for any language.

Three primary use cases are:

* Detecting and examining differences between databases.

* Replacing conventional linear up/down scripts with more flexible
  change management.

* Providing a consistant, safe and transparent access to stored routines.

Modyllic operates on "schemas", which can represent database, table, and
column definitions, routines, events, triggers, as well as data.  They are
stored in files as the SQL statements that would normally be used to create
the tables.  Schemas can be specified on the command line as:

* A DSN that connects to a live database
* A filename
* A directory
* A comma-separated list of more than one of the above (in which case
  all sources will be combined)

The flexibility of combining multiple sources makes it easy to deal
separately with the status quo schema vs. just the changes you intend
to make.

DSNs should include username and password if needed.  For convenience,
you can use ":" instead of ";" since shells often barf on ";" unless
you are careful with your quoting.  So, you could write either of the
following:

    mysql:host=database-server.example.org;dbname=MyDB;user=bobby;password=someThingClever
    mysql:host=database-server.example.org:dbname=MyDB:user=bobby:password=someThingClever

Available tools:

`migrate DSN SCHEMA` - Make the live database at DSN look like the one
described by SCHEMA.  You could think of it as running "sqldiff DSN
SCHEMA" and applying the diff to the live database.

`sqldiff SCHEMA1 SCHEMA2` - Produce the ALTER statements etc. that would
make SCHEMA1 look like SCHEMA2.  This is smarter than running "diff"
on two SQL dumps, because it actually parses SQL, ignores some things
that should be ignored, and is sensitive to the semantic context.
This essentially shows you what "migrate" would do given the same
arguments.

`sqldrop SCHEMA` - Produces the `DROP`, `DELETE`, etc. commands to delete
SCHEMA (but doesn't actually modify anything).  It's the equivalent of
`sqldiff SCHEMA /dev/null`.

`sqldump SCHEMA` - Produces the CREATE, INSERT, etc. commands to create
SCHEMA from scratch.  This is Modyllic's replacement for "mysqldump
-d" which gives you the output choice of several SQL dialects from the
very concise to its own metadata-rich format.  You could also think of
it as the equivalent of `sqldiff /dev/null SCHEMA`.

`sqltophp SCHEMA` - Generate a PHP helper class for the stored procs in the schema.

`cat FILENAME.sql | sqlcolorize` - Useful for debugging, just pipe some
SQL to it on STDIN and it will put a colorized, syntax-highlighted
version on STDOUT.

`sqlpreparse FILENAME.sql > FILENAME.sqlc` - Can be used to optimize the
performance of other tools by "pre-compiling" their input.  The other tools
will load a `.sqlc` file in preference to a `.sql` if it is newer.

Use `--help` on any of these tools to get more usage information.
