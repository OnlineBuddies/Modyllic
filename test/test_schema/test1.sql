CREATE TABLE foo ( id int not null, name char(30), primary key (id(3)), key (name), key(id) );
/*
TRUNCATE TABLE foo;

INSERT INTO foo (id,name) VALUES ( 1, "test" );

INSERT INTO foo (id,name) VALUES ( 2, "test" );
*/