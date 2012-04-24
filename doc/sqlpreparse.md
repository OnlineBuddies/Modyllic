sqlpreparse FILENAME.sql > FILENAME.sqlc - Can be used to optimize the
performance of other tools by "pre-compiling" their input.  The other tools
will load a .sqlc file in preference to a .sql if it is newer.
