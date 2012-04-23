Schemas can be specified on the command line as:

* A DSN that connects to a live database
* A filename
* A directory
* A comma-separated list of more than one of the above (in which case
  all sources will be combined)

The flexibility of combining multiple sources makes it easy to deal
separately with the status quo schema vs. just the changes you intend
to make.

DSNs should include username and password if needed.  For convenience,
you can use ":" instead of ";" since shells often barf on ";" unless
you are careful with your quoting.  So, you could write either of the
following:

    mysql:host=database-server.example.org;dbname=MyDB;user=bobby;password=someThingClever
    mysql:host=database-server.example.org:dbname=MyDB:user=bobby:password=someThingClever
