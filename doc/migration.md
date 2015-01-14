Migrating from Mouf 2.0 to Mouf 2.1
===================================

Migration from Mouf 2.0 to Mouf 2.1 is automatic. Simply load any page of your application and Mouf should automatically
detect that your configuration file (`mouf/MoufComponents.php`) is on the old format and migrate it to the new format.

**Important**: In Mouf 2.1, instances are stored in a separate file named `mouf/instances.php`. Also, Mouf will generate
a new class. The name of the class will depend on your autoload settings in your `composer.json` file. It will
be something like `Your\Namespace\Container`. If you are using a versioning system like GIT or SVN, you must
not forget to store these new files.