FLOW3 Command Reference
=======================

.. note:

  This reference uses ``./flow3`` as the command to invoke. If you are on
  Windows, this will probably not work, there you need to use ``flow3.bat``
  instead.

The commands in this reference are shown with their full command identifiers.
On your system you can use shorter identifiers, whose availability depends
on the commands available in total (to avoid overlap the shortest possible
identifier is determined during runtime).

To see the shortest possible identifiers on your system as well as further
commands that may be available, use::

  ./flow3 help

The following reference was automatically generated from code on 2012-06-07


Package *TYPO3.FLOW3*
---------------------


``typo3.flow3:help:help``
*************************

**Display help for a command**

The help command displays help for a given command:
./flow3 help <commandIdentifier>



Options
^^^^^^^

``--command-identifier``
  Identifier of a command for more details





``typo3.flow3:cache:flush``
***************************

**Flush all caches**

The flush command flushes all caches (including code caches) which have been
registered with FLOW3's Cache Manager. It also removes any session data.

If fatal errors caused by a package prevent the compile time bootstrap
from running, the removal of any temporary data can be forced by specifying
the option **--force**.

This command does not remove the precompiled data provided by frozen
packages unless the **--force** option is used.



Options
^^^^^^^

``--force``
  Force flushing of any temporary data



Related commands
^^^^^^^^^^^^^^^^

``typo3.flow3:cache:warmup``
  Warm up caches
``typo3.flow3:package:freeze``
  Freeze a package
``typo3.flow3:package:refreeze``
  Refreeze a package



``typo3.flow3:cache:warmup``
****************************

**Warm up caches**

The warm up caches command initializes and fills – as far as possible – all
registered caches to get a snappier response on the first following request.
Apart from caches, other parts of the application may hook into this command
and execute tasks which take further steps for preparing the app for the big
rush.





Related commands
^^^^^^^^^^^^^^^^

``typo3.flow3:cache:flush``
  Flush all caches



``typo3.flow3:configuration:show``
**********************************

**Show the active configuration settings**

The command shows the configuration of the current context as it is used by FLOW3 itself.
You can specify the configuration type and path if you want to show parts of the configuration.

./flow3 configuration:show --type Settings --path TYPO3.FLOW3.persistence



Options
^^^^^^^

``--type``
  Configuration type to show
``--path``
  path to subconfiguration separated by "." like "TYPO3.FLOW3





``typo3.flow3:core:setfilepermissions``
***************************************

**Adjust file permissions for CLI and web server access**

This command adjusts the file permissions of the whole FLOW3 application to
the given command line user and webserver user / group.

Arguments
^^^^^^^^^

``--commandline-user``
  User name of the command line user, for example "john
``--webserver-user``
  User name of the webserver, for example "www-data
``--webserver-group``
  Group name of the webserver, for example "www-data







``typo3.flow3:core:migrate``
****************************

**Migrate source files as needed**

This will apply pending code migrations defined in packages to all
packages that do not yet have those migration applied.

For every migration that has been run, it will create a commit in
the package. This allows for easy inspection, rollback and use of
the fixed code.



Options
^^^^^^^

``--status``
  Show the migration status, do not run migrations
``--packages-path``
  If set, use the given path as base when looking for packages
``--package-key``
  If set, migrate only the given package



Related commands
^^^^^^^^^^^^^^^^

``typo3.flow3:doctrine:migrate``
  Migrate the database schema



``typo3.flow3:core:shell``
**************************

**Run the interactive Shell**

The shell command runs FLOW3's interactive shell. This shell allows for
entering commands like through the regular command line interface but
additionally supports autocompletion and a user-based command history.







``typo3.flow3:doctrine:validate``
*********************************

**Validate the class/table mappings**

Checks if the current class model schema is valid. Any inconsistencies
in the relations between models (for example caused by wrong or
missing annotations) will be reported.

Note that this does not check the table structure in the database in
any way.





Related commands
^^^^^^^^^^^^^^^^

``typo3.flow3:doctrine:entitystatus``
  Show the current status of entities and mappings



``typo3.flow3:doctrine:create``
*******************************

**Create the database schema**

Creates a new database schema based on the current mapping information.

It expects the database to be empty, if tables that are to be created already
exist, this will lead to errors.



Options
^^^^^^^

``--output``
  A file to write SQL to, instead of executing it



Related commands
^^^^^^^^^^^^^^^^

``typo3.flow3:doctrine:update``
  Update the database schema
``typo3.flow3:doctrine:migrate``
  Migrate the database schema



``typo3.flow3:doctrine:update``
*******************************

**Update the database schema**

Updates the database schema without using existing migrations.

It will not drop foreign keys, sequences and tables, unless *--unsafe-mode* is set.



Options
^^^^^^^

``--unsafe-mode``
  If set, foreign keys, sequences and tables can potentially be dropped.
``--output``
  A file to write SQL to, instead of executing the update directly



Related commands
^^^^^^^^^^^^^^^^

``typo3.flow3:doctrine:create``
  Create the database schema
``typo3.flow3:doctrine:migrate``
  Migrate the database schema



``typo3.flow3:doctrine:entitystatus``
*************************************

**Show the current status of entities and mappings**

Shows basic information about which entities exist and possibly if their
mapping information contains errors or not.

To run a full validation, use the validate command.



Options
^^^^^^^

``--dump-mapping-data``
  If set, the mapping data will be output



Related commands
^^^^^^^^^^^^^^^^

``typo3.flow3:doctrine:validate``
  Validate the class/table mappings



``typo3.flow3:doctrine:dql``
****************************

**Run arbitrary DQL and display results**

Any DQL queries passed after the parameters will be executed, the results will be output:

doctrine:dql --limit 10 'SELECT a FROM TYPO3\FLOW3\Security\Account a'



Options
^^^^^^^

``--depth``
  How many levels deep the result should be dumped
``--hydration-mode``
  One of: object, array, scalar, single-scalar, simpleobject
``--offset``
  Offset the result by this number
``--limit``
  Limit the result to this number





``typo3.flow3:doctrine:migrationstatus``
****************************************

**Show the current migration status**

Displays the migration configuration as well as the number of
available, executed and pending migrations.





Related commands
^^^^^^^^^^^^^^^^

``typo3.flow3:doctrine:migrate``
  Migrate the database schema
``typo3.flow3:doctrine:migrationexecute``
  Execute a single migration
``typo3.flow3:doctrine:migrationgenerate``
  Generate a new migration
``typo3.flow3:doctrine:migrationversion``
  Mark/unmark a migration as migrated



``typo3.flow3:doctrine:migrate``
********************************

**Migrate the database schema**

Adjusts the database structure by applying the pending
migrations provided by currently active packages.



Options
^^^^^^^

``--version``
  The version to migrate to
``--output``
  A file to write SQL to, instead of executing it
``--dry-run``
  Whether to do a dry run or not
``--quiet``
  If set, only the executed migration versions will be output, one per line



Related commands
^^^^^^^^^^^^^^^^

``typo3.flow3:doctrine:migrationstatus``
  Show the current migration status
``typo3.flow3:doctrine:migrationexecute``
  Execute a single migration
``typo3.flow3:doctrine:migrationgenerate``
  Generate a new migration
``typo3.flow3:doctrine:migrationversion``
  Mark/unmark a migration as migrated



``typo3.flow3:doctrine:migrationexecute``
*****************************************

**Execute a single migration**

Manually runs a single migration in the given direction.

Arguments
^^^^^^^^^

``--version``
  The migration to execute



Options
^^^^^^^

``--direction``
  Whether to execute the migration up (default) or down
``--output``
  A file to write SQL to, instead of executing it
``--dry-run``
  Whether to do a dry run or not



Related commands
^^^^^^^^^^^^^^^^

``typo3.flow3:doctrine:migrate``
  Migrate the database schema
``typo3.flow3:doctrine:migrationstatus``
  Show the current migration status
``typo3.flow3:doctrine:migrationgenerate``
  Generate a new migration
``typo3.flow3:doctrine:migrationversion``
  Mark/unmark a migration as migrated



``typo3.flow3:doctrine:migrationversion``
*****************************************

**Mark/unmark a migration as migrated**

If *all* is given as version, all available migrations are marked
as requested.

Arguments
^^^^^^^^^

``--version``
  The migration to execute



Options
^^^^^^^

``--add``
  The migration to mark as migrated
``--delete``
  The migration to mark as not migrated



Related commands
^^^^^^^^^^^^^^^^

``typo3.flow3:doctrine:migrate``
  Migrate the database schema
``typo3.flow3:doctrine:migrationstatus``
  Show the current migration status
``typo3.flow3:doctrine:migrationexecute``
  Execute a single migration
``typo3.flow3:doctrine:migrationgenerate``
  Generate a new migration



``typo3.flow3:doctrine:migrationgenerate``
******************************************

**Generate a new migration**

If $diffAgainstCurrent is TRUE (the default), it generates a migration file
with the diff between current DB structure and the found mapping metadata.

Otherwise an empty migration skeleton is generated.



Options
^^^^^^^

``--diff-against-current``
  Whether to base the migration on the current schema structure



Related commands
^^^^^^^^^^^^^^^^

``typo3.flow3:doctrine:migrate``
  Migrate the database schema
``typo3.flow3:doctrine:migrationstatus``
  Show the current migration status
``typo3.flow3:doctrine:migrationexecute``
  Execute a single migration
``typo3.flow3:doctrine:migrationversion``
  Mark/unmark a migration as migrated



``typo3.flow3:package:create``
******************************

**Create a new package**

This command creates a new package which contains only the mandatory
directories and files.

Arguments
^^^^^^^^^

``--package-key``
  The package key of the package to create





Related commands
^^^^^^^^^^^^^^^^

``typo3.kickstart:kickstart:package``
  Kickstart a new package



``typo3.flow3:package:delete``
******************************

**Delete an existing package**

This command deletes an existing package identified by the package key.

Arguments
^^^^^^^^^

``--package-key``
  The package key of the package to create







``typo3.flow3:package:activate``
********************************

**Activate an available package**

This command activates an existing, but currently inactive package.

Arguments
^^^^^^^^^

``--package-key``
  The package key of the package to create





Related commands
^^^^^^^^^^^^^^^^

``typo3.flow3:package:deactivate``
  Deactivate a package



``typo3.flow3:package:deactivate``
**********************************

**Deactivate a package**

This command deactivates a currently active package.

Arguments
^^^^^^^^^

``--package-key``
  The package key of the package to create





Related commands
^^^^^^^^^^^^^^^^

``typo3.flow3:package:activate``
  Activate an available package



``typo3.flow3:package:list``
****************************

**List available packages**

Lists all locally available packages. Displays the package key, version and
package title and its state – active or inactive.





Related commands
^^^^^^^^^^^^^^^^

``typo3.flow3:package:activate``
  Activate an available package
``typo3.flow3:package:deactivate``
  Deactivate a package



``typo3.flow3:package:import``
******************************

**Import a package from a remote location**

Imports the specified package from a remote git repository.
The imported package will not be activated automatically.

Currently only packages located at forge.typo3.org are supported.
Note that the git binary must be available

Arguments
^^^^^^^^^

``--package-key``
  The package key of the package to import





Related commands
^^^^^^^^^^^^^^^^

``typo3.flow3:package:activate``
  Activate an available package
``typo3.flow3:package:create``
  Create a new package



``typo3.flow3:package:freeze``
******************************

**Freeze a package**

This function marks a package as **frozen** in order to improve performance
in a development context. While a package is frozen, any modification of files
within that package won't be tracked and can lead to unexpected behavior.

File monitoring won't consider the given package. Further more, reflection
data for classes contained in the package is cached persistently and loaded
directly on the first request after caches have been flushed. The precompiled
reflection data is stored in the **Configuration** directory of the
respective package.

By specifying **all** as a package key, all currently frozen packages are
frozen (the default).



Options
^^^^^^^

``--package-key``
  Key of the package to freeze



Related commands
^^^^^^^^^^^^^^^^

``typo3.flow3:package:unfreeze``
  Unfreeze a package
``typo3.flow3:package:refreeze``
  Refreeze a package



``typo3.flow3:package:unfreeze``
********************************

**Unfreeze a package**

Unfreezes a previously frozen package. On the next request, this package will
be considered again by the file monitoring and related services – if they are
enabled in the current context.

By specifying **all** as a package key, all currently frozen packages are
unfrozen (the default).



Options
^^^^^^^

``--package-key``
  Key of the package to unfreeze, or 'all'



Related commands
^^^^^^^^^^^^^^^^

``typo3.flow3:package:freeze``
  Freeze a package
``typo3.flow3:cache:flush``
  Flush all caches



``typo3.flow3:package:refreeze``
********************************

**Refreeze a package**

Refreezes a currently frozen package: all precompiled information is removed
and file monitoring will consider the package exactly once, on the next
request. After that request, the package remains frozen again, just with the
updated data.

By specifying **all** as a package key, all currently frozen packages are
refrozen (the default).



Options
^^^^^^^

``--package-key``
  Key of the package to refreeze, or 'all'



Related commands
^^^^^^^^^^^^^^^^

``typo3.flow3:package:freeze``
  Freeze a package
``typo3.flow3:cache:flush``
  Flush all caches



``typo3.flow3:routing:list``
****************************

**List the known routes**

This command displays a list of all currently registered routes.







``typo3.flow3:routing:show``
****************************

**Show informations for a route**

This command displays the configuration of a route specified by index number.

Arguments
^^^^^^^^^

``--index``
  The index of the route as given by routing:list







``typo3.flow3:routing:getpath``
*******************************

**Generate a route path**

This command takes package, controller and action and displays the
generated route path and the selected route:

./flow3 routing:getPath --format json Acme.Demo\\Sub\\Package

Arguments
^^^^^^^^^

``--package``
  Package key and subpackage, subpackage parts are separated with backslashes



Options
^^^^^^^

``--controller``
  Controller name, default is 'Standard'
``--action``
  Action name, default is 'index'
``--format``
  Requested Format name default is 'html'





``typo3.flow3:routing:routepath``
*********************************

**Route the given route path**

This command takes a given path and displays the detected route and
the selected package, controller and action.

Arguments
^^^^^^^^^

``--path``
  The route path to resolve







``typo3.flow3:security:importpublickey``
****************************************

**Import a public key**

Read a PEM formatted public key from stdin and import it into the
RSAWalletService.





Related commands
^^^^^^^^^^^^^^^^

``typo3.flow3:security:importprivatekey``
  Import a private key



``typo3.flow3:security:importprivatekey``
*****************************************

**Import a private key**

Read a PEM formatted private key from stdin and import it into the
RSAWalletService. The public key will be automatically extracted and stored
together with the private key as a key pair.



Options
^^^^^^^

``--used-for-passwords``
  If the private key should be used for passwords



Related commands
^^^^^^^^^^^^^^^^

``typo3.flow3:security:importpublickey``
  Import a public key



Package *TYPO3.FLUID*
---------------------


``typo3.fluid:documentation:generatexsd``
*****************************************

**Generate Fluid ViewHelper XSD Schema**

Generates Schema documentation (XSD) for your ViewHelpers, preparing the
file to be placed online and used by any XSD-aware editor.
After creating the XSD file, reference it in your IDE and import the namespace
in your Fluid template by adding the xmlns:* attribute(s):
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:f="http://typo3.org/ns/TYPO3/Fluid/ViewHelpers" ...>

Arguments
^^^^^^^^^

``--php-namespace``
  Namespace of the Fluid ViewHelpers without leading backslash (for example 'TYPO3\Fluid\ViewHelpers'). NOTE: Quote and/or escape this argument as needed to avoid backslashes from being interpreted!



Options
^^^^^^^

``--xsd-namespace``
  Unique target namespace used in the XSD schema (for example "http://yourdomain.org/ns/viewhelpers"). Defaults to "http://typo3.org/ns/<php namespace>".
``--target-file``
  File path and name of the generated XSD schema. If not specified the schema will be output to standard output.





Package *TYPO3.KICKSTART*
-------------------------


``typo3.kickstart:kickstart:package``
*************************************

**Kickstart a new package**

Creates a new package and creates a standard Action Controller and a sample
template for its Index Action.

For creating a new package without sample code use the package:create command.

Arguments
^^^^^^^^^

``--package-key``
  The package key, for example "MyCompany.MyPackageName





Related commands
^^^^^^^^^^^^^^^^

``typo3.flow3:package:create``
  Create a new package



``typo3.kickstart:kickstart:actioncontroller``
**********************************************

**Kickstart a new action controller**

Generates an Action Controller with the given name in the specified package.
In its default mode it will create just the controller containing a sample
indexAction.

By specifying the --generate-actions flag, this command will also create a
set of actions. If no model or repository exists which matches the
controller name (for example "CoffeeRepository" for "CoffeeController"),
an error will be shown.

Likewise the command exits with an error if the specified package does not
exist. By using the --generate-related flag, a missing package, model or
repository can be created alongside, avoiding such an error.

By specifying the --generate-templates flag, this command will also create
matching Fluid templates for the actions created. This option can only be
used in combination with --generate-actions.

The default behavior is to not overwrite any existing code. This can be
overridden by specifying the --force flag.

Arguments
^^^^^^^^^

``--package-key``
  The package key of the package for the new controller with an optional subpackage, (e.g. "MyCompany.MyPackage/Admin").
``--controller-name``
  The name for the new controller. This may also be a comma separated list of controller names.



Options
^^^^^^^

``--generate-actions``
  Also generate index, show, new, create, edit, update and delete actions.
``--generate-templates``
  Also generate the templates for each action.
``--generate-related``
  Also create the mentioned package, related model and repository if neccessary.
``--force``
  Overwrite any existing controller or template code. Regardless of this flag, the package, model and repository will never be overwritten.



Related commands
^^^^^^^^^^^^^^^^

``typo3.kickstart:kickstart:commandcontroller``
  Kickstart a new command controller



``typo3.kickstart:kickstart:commandcontroller``
***********************************************

**Kickstart a new command controller**

Creates a new command controller with the given name in the specified
package. The generated controller class already contains an example command.

Arguments
^^^^^^^^^

``--package-key``
  The package key of the package for the new controller
``--controller-name``
  The name for the new controller. This may also be a comma separated list of controller names.



Options
^^^^^^^

``--force``
  Overwrite any existing controller.



Related commands
^^^^^^^^^^^^^^^^

``typo3.kickstart:kickstart:actioncontroller``
  Kickstart a new action controller



``typo3.kickstart:kickstart:model``
***********************************

**Kickstart a new domain model**

This command generates a new domain model class. The fields are specified as
a variable list of arguments with field name and type separated by a colon
(for example "title:string" "size:int" "type:MyType").

Arguments
^^^^^^^^^

``--package-key``
  The package key of the package for the domain model
``--model-name``
  The name of the new domain model class



Options
^^^^^^^

``--force``
  Overwrite any existing model.



Related commands
^^^^^^^^^^^^^^^^

``typo3.kickstart:kickstart:repository``
  Kickstart a new domain repository



``typo3.kickstart:kickstart:repository``
****************************************

**Kickstart a new domain repository**

This command generates a new domain repository class for the given model name.

Arguments
^^^^^^^^^

``--package-key``
  The package key
``--model-name``
  The name of the domain model class



Options
^^^^^^^

``--force``
  Overwrite any existing repository.



Related commands
^^^^^^^^^^^^^^^^

``typo3.kickstart:kickstart:model``
  Kickstart a new domain model



