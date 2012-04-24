Thoughts on how we may restructure things to support SQL dialects.

To support different SQL dialects, some restructuring of the code will be
required.  This will also be required in order to support generating our own
SQL dialect as output.  The latter is important from the point of view of
reprocessing dumps.

There should be a top level object that you fetch, that is told which
dialect to use.

From this object you can:

* Construct a new schema...
  * Create an empty schema
  * Load a schema from a file:<ul>
      <li>Either our own dialect, or a database dump.  Essentially, we load
      the database's dialect with our extensions, then call a database
      specific fixup handler afterward to look for additional data, eg
      the SQLMETA table.</li></ul>
  * Load a schema from a database:<ul>
      <li>Interrogates the database for schema information, either through
      INFORMATION_SCHEMA or equivalent data dictionary tables or by
      the equivalent of 'SHOW CREATE ...' or worst case by calling the
      database's dump tool and parsing that output.</li>
      <li>This would also manually query the SQLMETA table for metadata.  As
      there is a standard way of translating this back into schema
      attributes, what's contained here rather then being in the actual
      schema is arbitrary.</li></ul>
       
* Validate a schema:
  * Move many things that produce load errors currently off into a
    separate validation stage-- this may mean logging warnings during load
    in addition to other post-load static analysis.

* Compare two schema...
  * And generate database specific update SQL.

* Generate SQL from a schema
  * Either database specific...<ul>
    <li>With and without weak foreign keys</li></ul>
  * Or using our own dialect
