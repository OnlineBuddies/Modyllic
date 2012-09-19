modyllic drop SCHEMA - Produces the DROP, DELETE, etc. commands to delete
SCHEMA (but doesn't actually modify anything).  It's the equivalent of
"sqldiff SCHEMA /dev/null".

modyllic drop can output SQL in a number of dialects with the --dialect option.  Currently included dialects are: ModyllicSQL (the default), MySQL, AssertMySQL (MySQL plus weak foreign keys included as regular foreign keys) and StrippedMySQL (MySQL less the SQLMETA table, which means no round-tripping of meta data.)
