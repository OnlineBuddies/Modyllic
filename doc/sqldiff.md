sqldiff SCHEMA1 SCHEMA2 - Produce the ALTER statements etc. that would
make SCHEMA1 look like SCHEMA2.  This is smarter than running "diff"
on two SQL dumps, because it actually parses SQL, ignores some things
that should be ignored, and is sensitive to the semantic context.
This essentially shows you what "migrate" would do given the same
arguments.
