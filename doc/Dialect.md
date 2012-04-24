The SQL dialect supported by these tools is a subset of the MySQL dialect
with a few extensions.  

Comments can be specified with C style comments, ie "/* */", Shell style
"#"-to-end-of-line and SQL style "--"-to-end-of-line.  Comments proceding a
command are documentation for it and will be preserved as best we can. 
Similarly, comments trailing a column definition are documentation for that
column.  The first comment in the file is assumed to be the documentation
for the schema itself, and is usually immediately followed by a DELIMITER
command.

A schema is a series of SQL commands, listed below, separated by a
delimiter.  The default delimiter is a semicolon (;), but you can change
this with the DELIMITER command.

Notes on the documentation below.  Items in square brackets are optional. 
Items in curly braces are alternatives, separated by pipes.  An ellipsis
marks an item that can be repeated any number of times. Items in all caps
are literal parts of the command.  Items in lower case and are variables,
either an identifier or specified below the command.

All CREATE statements can optionally have DEFINER, ALGORITHM and SQL
SECURITY arguments prior to the name of the thing they're creating.  If
included, these will be ignored.

Setting command delimiters:

    DELIMITER newdelim \n
    
    Where \n is a newline.  The only restriction on newdelim is that it may
    not contain whitespace.

SQL types:

    num_type | str_type | datetime_type | misc_type

num_type:

    {int_type(length) | dec_type(length,scale) | float_type(length,decimals)}
    [UNSIGNED] [ZEROFILL]

int_type:   

      BOOL
    | BOOLEAN
    | TINYINT
    | SMALLINT
    | MEDIUMINT
    | INT
    | INTEGER
    | BIGINT

dec_type:

      DEC
    | FIXED
    | NUMERIC
    | DECIMAL

float_type:

      FLOAT
    | REAL
    | DOUBLE
    | DOUBLE PRECISION

str_type:

    {char_type|text_type|compound_type}[(length)] 
    [{ASCII | UNICODE | CHARACTER SET charset] 
    [{BINARY | COLLATE collate}]

char_type:

      CHAR
    | BINARY
    | VARCHAR
    | VARBINARY

text_type:

      TINYTEXT
    | TINYBLOB
    | TEXT
    | BLOB
    | MEDIUMTEXT
    | MEDIUMBLOB
    | LONGTEXT
    | LONGBLOB

compound_type:

      ENUM(value,...)
    | SET(value,...)

datetime_type:

      DATE
    | DATETIME
    | TIMESTAMP
    | TIME
    | YEAR(length)

misc_type:        

      BIT
    | GEOMETRY
    | SERIAL

    SERIAL is only valid in CREATE TABLE statements and is there an alias for:

        BIGINT UNSIGNED NOT NULL UNIQUE AUTO_INCREMENT

    Note that NOT NULL UNIQUE AUTO_INCRMENT is not part of the type declaration.

Truncate:

    TRUNCATE [TABLE] tbl_name

    This is used to indicate that the entire contents of this table will be
    specified with INSERT statements in the schema.

Insert:

    INSERT INTO tbl_name row_data

row_data:

      (col_name,...) VALUES (value,...)
    | SET col_name=value, ...

Use:

    USE database_name
    
    This simply asserts that the current database name is what is specified
    here.  If we haven't seen a CREATE DATABASE command yet then this will
    set the database name.

Drop:

    DROP [IF EXISTS] thing name

thing:

    {TABLE | EVENT | PROCEDURE | FUNCTION | DATABASE | VIEW}
    
    Clears any definition so far for the thing specified.

Ignored:

    SET ...
    UPDATE ...
    CALL ...
    
    The latter two will print warnings

Databases:

    CREATE DATABASE name [create_specification]

    ALTER DATABASE name [create_specification]

create_specification:

      [DEFAULT] {CHARACTER SET | CHARSET} [=] charset_name
    | [DEFAULT] COLLATE [=] collation_name

    Sets the database name, default character set and collation for the
    current schema.

Events:

    CREATE EVENT name ON SCHEDULE schedule [ON COMPLETION completion] [status] DO event_body
    
    ALTER EVENT name [ON SCHEDULE schedule] [ON COMPLETION completion] [RENAME TO name] [status] [DO event_body]

schedule:

      AT timestamp [+ INTERVAL interval] ... 
    | EVERY interval
      [STARTS timestamp  [+ INTERVAL interval] ...]
      [ENDS timestamp [+INTERVAL interval] ...]
    
    Schedule timestamps and intervals can be expressions using (MySQL) built
    in functions and constants, but not user defined functions.

status:

    ENABLE | DISABLE | DISABLE ON SLAVE

event_body:

    Everything from here to the end of the command is included as the body. 
    If the first token of the body is BEGIN or call then it will be included
    literally.  Otherwise it will be wrapped in BEGIN and END.

Views:

  CREATE VIEW name view_def
  
  Views are only minimally supported.  view_def is not parsed and simply
  everything to the end of the command after the name of the view.

Stored routines:

    [comment]
    CREATE PROCEDURE proc_name( proc_arg, ... )
    [ARGS routine_args_type]
    [RETURNS proc_return_type]
    [ { CONTAINS SQL | NO SQL | READS SQL DATA | MODIFIES SQL DATA } ]
    [ { CONTAINS TRANSACTIONS | CALL IN TRANSACTION | NO TRANSACTIONS } ]
    [ [NOT] DETERMINISTIC ]
    [ COMMENT 'string' ]
    routine_body

    [comment]
    CREATE FUNCTION proc_name( func_arg, ... )
    [ARGS routine_args_type]
    [RETURNS sqltype]
    [ { CONTAINS SQL | NO SQL | READS SQL DATA | MODIFIES SQL DATA } ]
    [ { CONTAINS TRANSACTIONS | CALL IN TRANSACTION | NO TRANSACTIONS } ]
    [ [NOT] DETERMINISTIC ]
    [ COMMENT 'string' ]
    routine_body

proc_arg:
    
    [{IN INOUT OUT}] name type

func_arg:

    name type

proc_return_type:

     TABLE 
   | ROW 
   | COLUMN colname 
   | LIST colname
   | MAP (key, {value | ROW} 
   | STH 
   | NONE

routine_args_type:
     LIST
   | MAP

routine_body:

    RETURN expr | CALL expr | BEGIN {...} END
 
    The remainder of the routine definition, past the first token (that is,
    RETURN, CALL or BEGIN) are not parsed and simply passed through.  The
    parser scans until the next delimiter.

Tables:

    CREATE TABLE name ( {col_spec | key_spec},... ) table_option

table_option:

      [ENGINE=engine]
    | [[DEFAULT] {CHARACTER SET|CHARSET} [=] charset]
    | [[DEFAULT] COLLATE [=] collate]
    | [AUTO_INCREMENT [=] integer]
    | [COMMENT [=] 'string']
    
    AUTO_INCREMENT is used by MySQL to set the starting AUTO_INCREMENT
    value.  It is accepted but ignored and not emitted.  This is to support
    loading a schema from MySQL dump files.

col_spec:

    col_name sqltype [NOT NULL|NULL] [DEFAULT value] [ON UPDATE token]
    [AUTO_INCREMENT] [PRIMARY KEY] [COMMENT 'string'] [ALIASES (old_name,...)]
    [REFERENCES [WEAKLY] table (colname,...)]

    As noted in the type section, an sqltype of SERIAL implies:

        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT

    It also triggers the creation of an index with a UNIQUE constraint on this column.
    
    ON UPDATE is only valid in conjunction with TIMESTAMP and the default
    value (for a TIMESTAMP) is CURRENT_TIMESTAMP.
    
    If a column is NULL then the DEFAULT will be NULL.  

    You can specify foreign keys inline with REFERENCES.  If you declare a
    reference as weak with "WEAKLY" then it will only be included in
    generated SQL when you use a strict emitter.  This is intended to allow
    foreign keys on all columns during development, but only keeping them on
    selected columns in production due to performance reasons.

key_spec:

      [CONSTRAINT [c_name]] FOREIGN KEY [k_name] (col_name,...)
          REFERENCES tbl_name (col_name,...) [ON DELETE value] [ON UPDATE value]
    | PRIMARY KEY (col_name,...) [USING {BTREE|HASH}]
    | [{UNIQUE | FULLTEXT | SPACIAL}] KEY k_name (col_name,...) [USING {BTREE|HASH}]

    If you don't specify a constraint name or key name they will be
    generated for you following MySQL conventions.

