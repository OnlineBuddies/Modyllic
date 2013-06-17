Modyllic is an SQL parser and set of schema management tools, written in PHP
5.2, and useful for anyone working with SQL.

Modyllic is at its core, a tool for managing schema changes across branches.
Because it allows you to store your schema as DDL, it means that merges
between branches are easily handled using standard tools.  In addition to
analysis (dump and diff) tools, it also provides migration tools (migrate,
apply).  It also provides tools for making working with stored procedures in
PHP much easier-- it comes with a tool for generating a PHP wrapper for
calling your stored procedures that include type checking and fully inflated
return values, eg, you can return a table and get an array containing all of
the rows without a separate fetch step.

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

`modyllic migrate DSN SCHEMA` - Make the live database at DSN look like the one
described by SCHEMA.  You could think of it as running `modyllic diff -d MySQL DSN
SCHEMA` and applying the diff to the live database.

`modyllic diff SCHEMA1 SCHEMA2` - Produce the ALTER statements etc. that would
make SCHEMA1 look like SCHEMA2.  This is smarter than running "diff"
on two SQL dumps, because it actually parses SQL, ignores some things
that should be ignored, and is sensitive to the semantic context.
This essentially shows you what "migrate" would do given the same
arguments.

`modyllic drop SCHEMA` - Produces the `DROP`, `DELETE`, etc. commands to delete
SCHEMA (but doesn't actually modify anything).  It's the equivalent of
`modyllic diff SCHEMA /dev/null`.

`modyllic dump SCHEMA` - Produces the CREATE, INSERT, etc. commands to create
SCHEMA from scratch.  This is Modyllic's replacement for "mysqldump
-d" which gives you the output choice of several SQL dialects from the
very concise to its own metadata-rich format.  You could also think of
it as the equivalent of `modyllic diff /dev/null SCHEMA`.

`modyllic procstophp SCHEMA` - Generate a PHP helper class for the stored procs in the schema.

`cat FILENAME.sql | modyllic colorize` - Useful for debugging, just pipe some
SQL to it on STDIN and it will put a colorized, syntax-highlighted
version on STDOUT.

`modyllic preparse FILENAME.sql > FILENAME.sqlc` - Can be used to optimize the
performance of other tools by "pre-compiling" their input.  The other tools
will load a `.sqlc` file in preference to a `.sql` if it is newer.

Use `--help` on any of these tools to get more usage information.
