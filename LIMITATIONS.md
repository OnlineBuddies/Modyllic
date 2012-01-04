The SQL parser handles a subset of the MySQL dialect, including some of its
extensions.  Limitations on that dialect currently are:

  * String literals can not have a specfied character set.  All string
    literals in incoming SQL must be in UTF8, as they will be emitted to
    MySQL under "SET NAMES 'utf8'"

  * Similarly, you can not set a collation on your string literals.

  * Multiple string literals in a row are supported, however this is not
    maintained and they will be be emitted as a single string.  Ie:
        `'a' ' ' 'string'`
    Will be emitted as:
        `'a string'`

  * Hexidecimal literals are not supported and will produce a syntax error.

  * Boolean constants (TRUE, FALSE, true, false) are not supported.

  * Binary (so called "bitfield") literals are not supported and will
    produce a syntax error.

  * \N is not supported (use NULL instead).

  * Unquoted unicode identifiers are not supported.

  * Identifier length limits are not enforced.

  * The list of reserved words for the purposes of deciding weather or not
    an identifier must be quoted is all of them up to and including MySQL 5.6.

  * For the purposes of the parser, the reserved word list is a smaller list
    that's actually required for our purposes.  This does mean that you can
    use an unquoted reserved word as an identifier in input SQL.  The
    generated SQL will be correct, however, as it will be quoted there.

  * MySQL's conditional comments are passed are kind of supported:
  * * Without a version number, they're treated as a regular comment.
  * * With a version number the comment markers and version number are
      removed and parsing then continues.

  * Some non-MySQL data types that MySQL supports are not supported:

        CHARACTER VARYING(M)
        FLOAT4
        FLOAT8
        INT1
        INT2
        INT3
        INT4
        INT8
        LONG VARBINARY
        LONG VARCHAR
        LONG
        MIDDLEINT

There are some other miscellaneous limitations:

  * No support for renaming databases or tables.
  * No support for moving columns between tables.
  * No support for renaming events, procs or funcs. (Currently the old name would be dropped and the new one created.)
