This is a proposal and has not been implemented anywhere.  This is intended
to allow the tool to handle more complicated schema changes, for instance,
moving a column from one table to another without losing data.

These are conceptually similar to DDL triggers, but these are of course,
only run by [modyllic diff] or [modyllic migrate] and not on general changes to the
database.  They also insist on greater filtering then, for instance,
Oracle's DDL triggers.

    TRIGGER {BEFORE|AFTER} {CREATE|DROP} kind { table | proc | table.{column|index} }
    BEGIN
      -- SQL
    END

    kind is {TABLE | COLUMN | INDEX | PROC | FUNC | PROCEDURE | FUNCTION}
           PROC, FUNC and FUNCTION are all aliases for PROCEDURE

For example:

    DELIMITER ||
    CREATE TABLE foo ( id INT SERIAL PRIMARY KEY, bar INT, baz CHAR(20) )
    ||

Later the schema is changed to:

    DELIMITER ||
    CREATE TABLE foo ( id INT SERIAL PRIMARY KEY, baz CHAR(20) )
    ||
    CREATE TABLE bar ( id INT SERIAL PRIMARY KEY, bar INT )
    ||
    TRIGGER BEFORE DROP COLUMN foo.bar
    BEGIN
        CREATE TEMPORARY TABLE bar_backup AS SELECT id,bar FROM foo;
    END
    ||
    TRIGGER AFTER ADD TABLE bar
    BEGIN
        INSERT INTO bar SELECT id,bar FROM bar_backup;
    END
    ||

And the migrate script would generate:

    DELIMITER ||
    DROP PROCEDURE IF EXISTS migrate_before()
    ||
    CREATE PROCEDURE migrate_before()
    MODIFIES SQL
    BEGIN
        CREATE TEMPORARY TABLE bar_backup AS SELECT id,bar FROM foo;
    END
    ||
    DROP PROCEDURE IF EXISTS migrate_after()
    ||
    CREATE PROCEDURE migrate_after()
    MODIFIES SQL
    BEGIN
        INSERT INTO bar SELECT id,bar FROM bar_backup;
    END
    ||
    CALL migrate_before()
    ||
    CREATE TABLE bar (
        id INT SERIAL,
        bar INT,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 CHARACTER SET=utf8
    ||
    ALTER TABLE foo DROP bar
    ||
    CALL migrate_after()
    ||
    DROP PROCEDURE migrate_before
    ||
    DROP PROCEDURE migrate_after
    ||
