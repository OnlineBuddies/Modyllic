## There have been a number of similar, related projects over the years.  This page is intended to collect those.

### [Schemagic](http://schemagic.sourceforge.net/)

Schemagic appears to have been a project with similar intent.  It was written in Java and published to Sourceforge in 2006.  However, it's release was never completed.

### [SchemaSync](http://schemasync.org)

SchemaSync is a Python project that can generate SQL diffs between two MySQL instances.  It compares live databases only and cannot read DDL itself.

### [SQL::Transalator](https://metacpan.org/module/SQL::Translator)

A Perl project that does essentially the same thing.  It's focus is on translating DDL between different database vendors, but it provides a diff tool that can generate alter statements as well.  Outside the CPAN it can be found as [the SQL Fairy](http://sqlfairy.sourceforge.net/).

### [SQLDiff](https://github.com/christeredvartsen/sqldiff/)

A PHP 5.3 project that will generate ALTER statements when comparing between two MySQL XML dump files.

### [SchemaCrawler](http://schemacrawler.sourceforge.net/)

A JDBC tool for showing the differences between schemas.

### [SchemaDiff](https://github.com/iamcal/SchemaDiff)

A simple PHP tool for running diff on files containing DDL representations of schema.