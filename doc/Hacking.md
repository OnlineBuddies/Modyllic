So you want to modify the SQL parser itself?  This aims to be a guide to
it's major components.  First, here's an overview of the classes involved
and how they relate to each other:

  * SQL -- Loads the full suite of SQL parser classes.  Provides four
           utility static methods:
  * *          quote\_ident -- Quotes MySQL identifiers, if needed.
  * *          quote\_str -- Quotes MySQL strings.
  * *          is\_reserved -- Identifies MySQL reserved words
  * *          valid\_ident -- Identifies valid MySQL unquoted identifiers.
  * SQL\_Commandline -- Provides utility methods for commandline scripts
  * SQL\_Diff -- Calculates differences between schema (and produces SQL\_Changeset objects)
  * SQL\_Generator -- Generates SQL from a schema or a changeset
  * SQL\_Parser -- Parses a token stream into a schema
  * SQL\_Schema -- Represents a complete database schema
  * SQL\_Schema\_Loader -- Loader methods for file and database
  * SQL\_Schema\_FromDB -- The actual implementation of the database loader
  * SQL\_Tokenizer -- Turns an SQL file into a stream of Token objects
  * SQL\_Types -- Represents all of the various column data types

So you want to...


Add or update a type?
---------------------
First, you may need to add a new reserved word if this is an entirely new
type.  To do that look at the bottom of SQL\_Tokenizer.

Next up is SQL\_Types, where (most) everything about types lives.  At the top
is the mapping between reserved words and type classes.  The type interface
is fairly simple.  It's expected to be able to do a few things:

  * toSql -- Generate the canonical SQL representation of this type.
             This can optionally take a type we're changing from as an
             argument-- mostly useful for including changes of column
             charset or collation when the overall defaults changed. 
  * equalTo -- Compares two types to see if they're semanticaly identical.
  * cloneFrom -- Updates the current object from another one, mostly used to
                 mutate from one type to another.
  * normalize -- Takes a value of this type and normalizes it... for
                 instance, if you give a value of 0 for a date, it becomes
                 "0000-00-00 00:00:00".

Once you've added your new type, you should take a look at SQL\_Parser's
get\_type method.  You may need to up date it to support your new type.  For
simple types this probably won't be necessary.

Support a new command?
----------------------
If this will involve a new reserved word, you'll need to edit SQL\_Tokenizer. 
The list is kept in alphabetical order at the bottom.  Note that longer
reserved words are preferred over shorter ones, so for instance 
"PRIMARY KEY" will be matched in preference to "KEY".

When all of your reserved words are accounted for you, you'll need to edit
SQL\_Parser.  New commands are added by adding a method named cmd\_CMDNAME. 
So for instance, DELIMITER is cmd\_DELIMITER.  CREATE commands are named
cmd\_CREATE\_THING.  Where THING might be TABLE, resulting in
cmd\_CREATE\_TABLE.  

From there you can start pulling data off of the token stream.  You can get
the next token irrespective of type with next() or look at it without taking
with peek\_next().  By default, whitespace is ignored.  If you need
whitespace, pass TRUE to next()/peek\_next() and you'll get back
SQL\_Token\_Whitespace when it's found.

You can also always look at the last token returned by next() with cur(). 
Better though is to use the get\_<thing> or assert\_<thing> suite of methods
where <thing> is reserved, ident, num, symbol and string.  These will throw
an exception if the wrong type is specified.  Most of them let you be even
more specific, for instance, assert\_reserved can optionally take an array of
valid reserved words...  any other reserved word will throw an exception.

Also useful are get\_list, which will fetch everything up to the next ')' and
returns it as a string, and get\_array which does the same but returns the
list as an array.

If you need to pull in a type declaration, get\_type() will do this for you.

If you need an argument list (as might be given to a proc) get\_args() does
this.

If you need the remainder of the current command as a string (for instance,
as a proc body), you can get this with rest().

Generally you don't need to talk directly to the tokenizer, but if you do,
it's in the tok property.

If you need to throw an error, do it with: throw $this->error( $message );

Once you've parsed out values from your new command, you need to put them
somewhere.  You'll need to visit SQL\_Schema for this.  You'll likely need to
add something to the SQL\_Schema class itself to hold instances of your new
type of thing and a new class for it as well.

Next, you'll want to visit SQL\_Diff and add comparisons for your new type of
stuff to it's calculate\_changeset method.  You'll also likely want to update
SQL\_Changeset in the same file, to have a place to store these differences.

And then you'll want to visit SQL\_Generate to add entires to the create,
drop and alter methods.  The first two take a schema object and generate SQL
from it.  The final one takes a changeset object and generates SQL from it.

And finally, you may need to update SQL\_Schema\_Loaders to know how to load
this sort of command.  You shouldn't have to do anything for the file
loader, but the database loader will likely need updating.


Load from a new datasource?
---------------------------
Your work will be in SQL\_Schema\_Loader. Look at how it and SQL\_Schema\_FromDB
work.  If your data source provides sufficient metadata, you could actually
generate a Schema object directly from that.  If some of your data is stored
in SQL, you can feed snippets of SQL (in the form of an SQL\_Tokenizer
object) to SQL\_Parser and have it update your schema.


-----------------------------------------------------------------------------

Example new feature:

****** TODO: Some of the examples need minor fixes.  The latter parts could
use elaboration. ******

Goal: 
Add support for tables whose contents are defined as part of the schema.

Implemented as:

  * `TRUNCATE [TABLE] tablename` -- Marks tablename as having its contents managed
in the schema.
  * `INSERT INTO tablename (cols) VALUES (values)` -- Declares a row as existing
in the previously truncated tablename.

Implmentation steps:

**In SQL/Schema.php:**

* Add new $data property to SQL\_Table.  This will be an array of rows of data.
* Add clear\_data method that initializes the data property to an empty array.
* Add add\_data method that adds rows to the data property.

    public $data;

    /**
     * Clears the data associated with this table.  Also initializes it,
     * allowing data to be inserted into it.
     */
    function clear\_data() {
        $this->data = array();
    }

    /**
     * Add a row of data to this table
     * @throws Exception when data is not yet initialized.
     */
    function add\_data( $row ) {
        if ( ! isset($this->data) ) {
            throw new Exception("Cannot add data to ".$this->table().
                ", not initialized for schema supplied data-- call TRUNCATE first.");
        }
        $this->data[] = $row;
    }

**In SQL/Tokenizer.php:**

* Add add TRUNCATE and "INSERT INTO" as reserved words.  TABLE is already a
  keyword of course.

**http://dev.mysql.com/doc/refman/5.1/en/truncate-table.html **

* Double check the syntax for the command: TRUNCATE [TABLE] tbl\_name

**In SQL/Parser.php:**

* Add a cmd\_TRUNCATE method and begin with a comment with the syntax above.
  - First we peek at the next value and see if it's TABLE.  If it is, we call
    get\_reserved() which will pull it out of the token stream and ensure it's
    not another type.
  - Next, we call get\_ident() and store the result as our table name.
  - Then we make sure that table actually already exists-- if it doesn't we
    throw an error.
  - Finally we call the clear\_data() method on our table.

    function cmd\_TRUNCATE() {
        // TRUNCATE [TABLE] tbl\_name
        if ( $this->peek\_next()->value() == 'TABLE' ) {
            $this->get\_reserved();
        }
        $table\_name = $this->get\_ident();
        if ( ! isset($this->schema->tables[$table\_name]) ) {
            throw $this->error( "Can't TRUNCATE table $table\_name before it is CREATEd" );
        }
        $this->schema->tables[$table\_name]->clear\_data();
    }

**http://dev.mysql.com/doc/refman/5.1/en/insert.html **

* Review the INSERT syntax.  We don't want to actually support all of it, as
  we're only inserting static data.  I've settled on this limited subset:

    INSERT INTO tbl\_name row\_data
    row\_data:
        (col\_name,...) VALUES (value,...)
      | SET col\_name=value, ...

**In SQL/Tokenizer.php:**

* Verify that all VALUES and SET are in our reserved word list.  As it
  happens, SET is as it's also a data type, but VALUES is not.  Add it.

**In SQL/Parser.php:**

* Add a cmd\_INSERT\_INTO method, with the syntax from above.
  * Fetch the table name iwth get\_ident().
  * Do the same check for its existance we did for TRUNCATE
  * Set up a little branch on what the next token is... if it's a ( then
    we'll expect the first form.  If it's SET then we'll expect the second
    form.  If it's none of the above, we throw an error.
  * For the first form:
    * The things to look for here are the use of get\_array() and get\_token\_array()
    * We use get\_token\_array() because values will need to be normalized and
      the type normalization methods expect tokens not raw values.

            $this->get\_symbol();
            $columns = $this->get\_array();
            $this->get\_reserved('VALUES');
            $this->get\_symbol('(');
            $values = $this->get\_token\_array();
            if ( count($columns) != count($values) ) {
                throw $this->error("INSERT INTO column count doesn't match value count" );
            }
            $row = array\_combine( $columns, $values );

  * For the second form:
    * We want to process as long as we haven't hit the end of the command,
      so we setup a while loop.  For each iteration of the loop
    * We use get\_reserved("SET") to pull off the next token and assert that
      it's a SET reserved word.
    * We get\_ident() for the column name.
    * We get\_symbol('=') for the assignment operator.
    * And finally we use next() to get the value, as it's of an unknown type.
    * And then we assign those to our $row array.

            while ( ! $this->peek\_next() instanceOf SQL\_Token\_EOC ) {
                $this->get\_next("SET");
                $col = $this->get\_ident();
                $this->get\_symbol('=');
                $value = $this->next();   
                $row[$col] = $value;
            }

  * Now that we have our row of data, we need to normalize its values, and
    incidentally, validate that all of the columns exist.
    * To normalize, we call the column's type's normalize method.
  * Now that we finally have a completed row, we add it to the table definition.

        // INSERT INTO tbl\_name row\_data
        // row\_data:
        //     (col\_name,...) VALUES (value,...)
        //   | SET col\_name=value, ... 
        $table\_name = $this->get\_ident();
        if ( ! isset($this->schema->tables[$table\_name]) ) {
            throw $this->error( "Can't INSERT INTO table $table\_name before it is CREATEd" );
        }
        $table = $this->schema->tables[$table\_name];
        $row = array();
        if ( $this->peek\_next()->value() == '(' ) {
            $this->get\_symbol();
            $columns = $this->get\_array();
            $this->get\_reserved('VALUES');
            $this->get\_symbol('(');
            $values = $this->get\_token\_array();
            if ( count($columns) != count($values) ) {
                throw $this->error("INSERT INTO column count doesn't match value count" );
            }
            $row = array\_combine( $columns, $values );
        }
        else if ( $this->peek\_next()->value() == "SET" ) {
            while ( ! $this->peek\_next() instanceOf SQL\_Token\_EOC ) {
                $this->get\_next("SET");
                $col = $this->get\_ident();
                $this->get\_symbol('=');   
                $value = $this->next();   
                $row[$col] = $value;      
            }
        }
        else {
            throw $this->error( "Expected '(col\_names) VALUES (values)' or 'SET col\_name=value,...'" );
        }
        foreach ($row as $col\_name=>$value) {
            if ( ! isset($table->columns[$col\_name]) ) {
                throw $this->error( "INSERT INTO references $col\_name in $table\_name but $col\_name doesn't exist" );
            }
            $col = $table->columns[$col\_name];
            $norm\_value = $col->type->normalize($value);
            $row[$col\_name] = $norm\_value;
        }
        $table->add\_row( $row );

**In SQL/Generator.php:**

* I like to go write the generators for CREATE/DROP at this point, as
  they're easy to do and let me start getting output.
* For this, we don't need to write DROP code, because of course, dropping a
  table will drop the data in it.
* As our change was to the table schema, we'll go down to the create\_table() method.
* If there's data then we'll just loop over it and add our inserts.
* This is MySQL specific right now, so I'm going to indulge myself and use
  the MySQL SET insert form because I find it more readable.
* Special notice should be taken to quoting all of one's identifiers.

        if ( isset($table->data) ) {
            foreach ($table->data as $row) {
                $sql .= self::delimiter() . "\n";
                $sql .= "INSERT INTO ".SQL::quote\_ident($table->name);
                foreach ($row as $col=>$value) {
                    $sql .= " SET ".SQL::quote\_ident($col)."=".$value;
                }
                $sql .= "\n";
            }
        }

* We can go run sqldump now on a schema with these features in it and see
  our lovely insert statements echoed there!

**In SQL/Diff.php:**

* Since our changes were to a table, we'll need to update the
  SQL\_Table\_Changeset class first, to include a place to store changed data. 
  So in the add, remove and update lists, I'm adding a "data" key.
* We'll also add add\_row, remove\_row and update\_row methods.  The only
  special thing here is the update\_row method, that needs to know not only
  the data that's changed, but also how to identify the previous row in an
  update.
* We also have to update has\_changes() so that data changes count as table changes.
* Now that SQL\_Table\_Changeset is setup, we can visit calculate\_changeset at
  the top of the file.  Midway through it we'll find the comment "For each
  table, find table changes"  This is what we want to update.
* Zooming down to the bottom of that, after the index changes, we'll add our
  checks for data changes.

**In SQL/Generator.php:**

* At the end of the alter\_table method we'll add our generators for the changed data.

**...**

* Now loading and diffing data between schemas on disk works fine, but we
  have to load data from the database somehow.  We can't just load any data
  that happens to be there-- we need to mark a table as having data we need. 
  So we need to add some meta data to the table.  The way we associate
  metadata with arbitrary SQL elements is via their COMMENTs.  

**In SQL/Diff.php:**

* First, we need a place for this in the changeset.  As this is an attribute
  on the table itself, we'll add it to SQL\_Table\_Options.  And then we just
  record if the one or the other doesn't declare static data:

            if ( isset($totable->data) != isset($fromtable->data) ) {
                $tablediff->update\_option( "data", isset($totable->data) );
            }

**In SQL/Generate.php:**

* Now, metadata is added as part of options, so we go down to table\_options
  and just insert a check for it:

        if ( isset($table->data) ) {
            // Ugly lil bit here, 'cause $table could be SQL\_Table or SQL\_Table\_Options
            $value = is\_array($table->data) ? TRUE : $table->data;
            $meta['static'] = $value;
        }

  The existing code will insert the metadata into a comment if any was set.

**In SQL/Parser.php:**

* We also need to add support for this bit of metadata to the parser.  We go
  to cmd\_CREATE\_TABLE and go down to the section where it lodas metadata
  from COMMENTs and add:

                if ( isset($meta['static']) ) {
                    if ( $meta['static'] ) {
                        $this->table->clear\_data();
                    }
                }

**In SQL/Schema/FromDB.php:**

* Now we have to go load the data if the table is indeed static when loading from the database.

        // Look for data to load...
        foreach ($schema->tables as $name=>$table) {
            if ( isset($table->data) ) {
                $data\_sth = $this->query("SELECT * FROM ".SQL::quote\_ident($name));
                while ( $data\_row = $data\_sth->fetch(PDO::FETCH\_ASSOC) ) {
                    $table->add\_row( $data\_row );
                }
            }
        }
