#!/usr/bin/env php
<?php
/**
 * Copyright © 2012 Online Buddies, Inc. - All Rights Reserved
 *
 * @package Modyllic
 * @author bturner@online-buddies.com
 */


define('AUTO_SETUP_ENV', false);

require_once implode(DIRECTORY_SEPARATOR, array(
    dirname(__FILE__), '..', 'test_environment.php' ));

plan(20);

$new_include_path = Modyllic_AutoLoader::get_new_include_path();
isnt( $new_include_path, get_include_path(), 'We know how to add ourselves to the include path' );

is( Modyllic_AutoLoader::class_to_filename('Example'), 'Example.php', 'Simple classes' );
is( Modyllic_AutoLoader::class_to_filename('AutoLoader_Example'), 'AutoLoader/Example.php', '<= 5.2 "namespaces"');
is( Modyllic_AutoLoader::class_to_filename('AutoLoader_Example_Test'), 'AutoLoader/Example/Test.php', '<= 5.2 "namespaces" more then one level');
is( Modyllic_AutoLoader::class_to_filename('\main\Example'), 'main/Example.php', '>= 5.3 namespaces');
is( Modyllic_AutoLoader::class_to_filename('\main\test\Example'), 'main/test/Example.php', '>= 5.3 namespaces, more then one level');
is( Modyllic_AutoLoader::class_to_filename('\main_test\Example'), 'main_test/Example.php', '>= 5.3 namespaces with underscores');
is( Modyllic_AutoLoader::class_to_filename('\main_test\Example_Test'), 'main_test/Example/Test.php', '>= 5.3 namespaces with underscores and 5.2 style class name');

$old_include = get_include_path();
set_include_path( dirname(__FILE__) );

$example_path = Modyllic_AutoLoader::find_in_path('AutoLoader/Example.php');
ok( isset($example_path), 'We can find Example.php in our path' );
$nosuchfile_path = Modyllic_AutoLoader::find_in_path('AutoLoader/NoSuchFile.php');
ok( ! isset($nosuchfile_path), 'But not NoSuchFile.php' );

ok( !class_exists('AutoLoader_Example',false), 'Our AutoLoader example class does not yet exist');
Modyllic_AutoLoader::autoload('AutoLoader_Example');
ok( class_exists('AutoLoader_Example',false), 'Our AutoLoader example class has been loaded');

ok( !class_exists('AutoLoader_NoSuchFile',false), 'Our non-existant AutoLoader class does not exist');
Modyllic_AutoLoader::autoload('AutoLoader_NoSuchFile');
ok( !class_exists('AutoLoader_NoSuchFile',false), 'Our non-existant AutoLoader class still does not exist');

set_include_path($old_include);

$autoloaders = spl_autoload_functions();

ok( ! $autoloaders, 'No autoloaders have yet been configured' );

__setup_env();

is( $new_include_path, get_include_path(), 'Installing the autoloader resulted in an appropriately updated include path');

$autoloaders = spl_autoload_functions();

ok( $autoloaders, 'Autoloaders exist and have been configured' );
is( count($autoloaders), 1, 'We have exactly one autoloader' );

ok( !class_exists('Modyllic_SQL',false), 'We\'ve not yet loaded Modyllic_SQL' );
ok( class_exists('Modyllic_SQL'), 'But we can do so via our autoloader' );
