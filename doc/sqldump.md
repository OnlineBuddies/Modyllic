sqldump SCHEMA - Produces the CREATE, INSERT, etc. commands to create
SCHEMA from scratch.  This is Modyllic's replacement for "mysqldump
-d" which gives you the output choice of several SQL dialects from the
very concise to its own metadata-rich format.  You could also think of
it as the equivalent of "sqldiff /dev/null SCHEMA".
