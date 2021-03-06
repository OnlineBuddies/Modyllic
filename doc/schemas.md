Schemas can be specified on the command line as:

* A DSN that connects to a live database
* A filename
* A directory

The flexibility of combining multiple sources makes it easy to deal
separately with the status quo schema vs. just the changes you intend
to make.

DSNs should include username and password if needed.  For convenience,
you can use ":" instead of ";" since shells often barf on ";" unless
you are careful with your quoting.  So, you could write either of the
following:

    mysql:host=database-server.example.org;dbname=MyDB;username=bobby;password=someThingClever
    mysql:host=database-server.example.org:dbname=MyDB:username=bobby:password=someThingClever
