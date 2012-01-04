This summarizes the extensions to SQL that we support.  For full details of
the subset of SQL that we support, see the SQL_DIALECT document.

    CREATE PROCEDURE proc_name( proc_arg, ... )
    [RETURNS proc_return_type]
    [ARGS {LIST|MAP}]
    [transaction_spec]
    [{CONTAINS TRANSACTIONS | CALL IN TRANSACTION | NO TRANSACTIONS}]

    CREATE FUNCTION proc_name( func_arg, ... )
    [ARGS {LIST|MAP}]
    [transaction_spec]

The procedure return value is used by the code generator to determine what
sort of value, if any, the helper for this stored proc should return.  If
nothing is specified then nothing is returned.

  * `TABLE`           -- Fetches all of the rows and returns an array of them
  * `ROW`             -- Fetches one row and returns it
  * `COLUMN colname`  -- Fetches one row and returns colname from it
  * `LIST colname`    -- Fetches colname from all matching rows and returns an array of them
  * `MAP (key,value)` -- Fetches all of the rows and uses the columns key and
                         value to build a map and returns that.
  * `MAP (key,ROW)`   -- Fetches all of the rows and uses the column key to
                         build a map with the entire row as the value, and
                         returns that.
  * `STH`             -- Returns a raw statement handle.
  * `NONE`            -- No return value, the default.

Stored procedures and functions whose name begins with an underscore (_)
will not have a helper method generated for them.

The optional `ARGS` attribute for both procedures and functions declares how
you want to pass in arguments.  Either as a list, or named. 
For PHP, `LIST` means regular method arguments and `MAP` means accepting an
array with pairs matching the argument names of the stored proc.  The key
names should not include a leading p_ if your proc argument names start with
that.

(If we were supporting a language that supports named arguments then `MAP`
would use those.  For something like Perl 6, where positional arguments can
be passed in by name, `LIST` and `MAP` would produce the same code.)

---

transaction_spec:

    [{CONTAINS TRANSACTIONS | CALL IN TRANSACTION | NO TRANSACTIONS}]

These allow you to hint how your stored proc uses transactions.

  * `CONTAINS TRANSACTIONS` -- You MUST NOT be in a transaction when you call
    this proc.  The proc contains transactions and can not be called if you
    have an active transaction.

  * `CALL IN TRANSACTION` -- You MUST be in a transaction when you call this.
    If you are not, one will be created for you and commited after it completes.

  * `NO TRANSACTIONS` -- This proc is transaction agnostic and can be used
    inside and outside transactions.

---

In `CREATE TABLE` statements, your column definitions can have an additional
list of other names the field has been known by.  If you migrate to this
version from a version that had a field with one of those names, it will
rename issue a rename, rather then dropping the old field and adding the new
one.  You do this by adding `ALIASES (fields,...)` to the columnspec.  Eg:

    total FLOAT NOT NULL ALIASES (amount),

Would say that we have a column named total that in other versions was
called amount.  If the migration tool finds a column named amount it will
rename it to total.

