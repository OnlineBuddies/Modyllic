modyllic dump SCHEMA - Produces the CREATE, INSERT, etc. commands to create
SCHEMA from scratch.  This is Modyllic's replacement for "mysqldump
-d" which gives you the output choice of several SQL dialects from the
very concise to its own metadata-rich format.  You could also think of
it as the equivalent of "sqldiff /dev/null SCHEMA".

modyllic dump can output SQL in a number of dialects with the --dialect option.  Currently included dialects are: ModyllicSQL (the default), MySQL, AssertMySQL (MySQL plus weak foreign keys included as regular foreign keys) and StrippedMySQL (MySQL less the SQLMETA table, which means no round-tripping of meta data.)
