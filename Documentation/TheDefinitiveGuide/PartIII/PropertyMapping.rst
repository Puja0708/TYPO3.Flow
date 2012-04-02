================
Property Mapping
================

.. sectionauthor:: Sebastian Kurfürst <sebastian@typo3.org>

.. TODOs: extract TypeConverter reference from PHPDoc

The Property Mappers task is to convert *simple types*, like arrays, strings, numbers,
to objects. This is most prominently needed in the MVC framework: When a request
arrives, it contains all its data as simple types, that is strings, and arrays.

We want to help the developer thinking about *objects*, that's why we try to
transparently convert the incoming data to its correct object representation.
This is the objective of the *Property Mapper*.

At first, we show some examples on how the property mapper can be used, and then
the internal structure is explained.

The main API of the ``PropertyMapper`` is very simple: It just consists of one method
``convert($source, $targetType)``, which receives input data as the first argument,
and the target type as second argument. This method returns the built object of type
``$targetType``.

Example Usage
=============

The most simple usage is to convert simple types to different simple types, i.e.
converting a numeric ``string`` to a ``float`` number::

	// $propertyMapper is of class TYPO3\FLOW3\Property\PropertyMapper
	$result = $propertyMapper->convert('12.5', 'float');
	// $result == (float)12.5

This is of course a really conceived example, as the same result could be archieved
by just casting the numeric string to a floating point number.

Our next example goes a bit further and shows how we can use the Property Mapper
to convert an array of data into a domain model::

	/**
	 * @FLOW3\Entity
	 */
	class TYPO3\MyPackage\Domain\Model\Person {
		/**
		 * @var string
		 */
		protected $name;

		/**
		 * @var \DateTime
		 */
		protected $birthDate;

		/**
		 * @var TYPO3\MyPackage\Domain\Model\Person
		 */
		protected $mother;
		// ... furthermore contains getters and setters for the above properties.
	}

	$inputArray = array(
		'name' => 'John Fisher',
		'birthDate' => '1990-11-14T15:32:12+00:00'
	);
	$person = $propertyMapper->convert($inputArray, 'TYPO3\MyPackage\Domain\Model\Person');

	// $person is a newly created object of type TYPO3\MyPackage\Domain\Model\Person
	// $person->name == 'John Fisher'
	// $person->birthDate is a DateTime object with the correct date set.

We'll first use a simple input array::

	$input = array(
	  'name' => 'John Fisher',
	  'birthDate' => '1990-11-14T15:32:12+00:00'
	);

After calling ``$propertyMapper->convert($input, 'TYPO3\MyPackage\Domain\Model\Person'),
we receive an ew object of type ``Person`` which has ``$name`` set to ``John Fisher``,
and ``$birthDate`` set to a ``DateTime`` object of the specified date. You might
now wonder how the PropertyMapper knows how to convert ``DateTime`` objects and
``Person`` objects? The answer is: It does not know that. However, the PropertyMapper
calls specialized *Type Converters* which take care of the actual conversion.

In our example, three type converters are called:

* First, to convert 'John Fisher' to a string (required by the annotation in the
  domain model), a ``StringConverter`` is called. This converter simply passes
  through the input string, without modification.
* Then, a ``DateTimeConverter`` is called, whose responsibility is to convert the
  input string into a valid ``DateTime`` object.
* At the end, the ``Person`` object still needs to be built. For that, the
  ``PersistentObjectConverter`` is responsible. It creates a fresh ``Person`` object,
  and sets the ``$name`` and ``$birthDate`` properties which were already built
  using the type converters above.

This example should illustrate that property mapping is a recursive process, and
the PropertyMappers task is exactly to orchestrate the different ``TypeConverters``
needed to build a big, compound object.

The ``PersistentObjectConverter`` has some more features, as it supports fetching
objects from the persistence layer if an identity for the object is given. Both
the following inputs will result in the corresponding object to be fetched from
the persistence layer::

	$input = '14d20100-9d70-11e0-aa82-0800200c9a66';
	// or:
	$input = array(
	  '__identity' => '14d20100-9d70-11e0-aa82-0800200c9a66'
	);

	$person = $propertyMapper->convert($input, 'MyCompany\MyPackage\Domain\Model\Person');
	// The $person object with UUID 14d20100-9d70-11e0-aa82-0800200c9a66 is fetched from the persistence layer

In case some more properties are specified in the array (besides ``__identity``),
the submitted properties are modified on the fetched object. These modifications are
not automatically saved to the database at the end of the request, you need to pass
such an instance to ``update`` on the corresponding repository to persist the changes.

So, let's walk through a more complete input example::

	$input = array(
	  '__identity' => '14d20100-9d70-11e0-aa82-0800200c9a66',
	  'name' => 'John Doe',
	  'mother' => 'efd3b461-6f24-499d-97bc-309dfbe01f05'
	);

In this case, the following steps happen:

* The ``Person``object with identity ``14d20100-9d70-11e0-aa82-0800200c9a66`` is
  fetched from persistence.
* The ``$name`` of the fetched ``$person`` object is updated to ``John Doe``
* As the ``$mother`` property is also of type ``Person``, the ``PersistentObjectConverter``
  is invoked recursively. It fetches the ``Person`` object with UUID
  ``efd3b461-6f24-499d-97bc-309dfbe01f05``, which is then set as the ``$mother``
  property of the original person.

Here, you see that we can also set associations using the Property Mapper.

Configuring the Conversion Process
==================================


It is possible to configure the conversion process by specifying a
``PropertyMappingConfiguration`` as third parameter to ``PropertyMapper::convert()``.
If no ``PropertyMappingConfiguration`` is specified, the ``PropertyMappingConfigurationBuilder``
automatically creates a default ``PropertyMappingConfiguration``.

In most cases, you should use the ``PropertyMappingConfigurationBuilder`` to create a new
PropertyMappingConfiguration, so that you get a safe-by-default configuration::

	// $propertyMappingConfigurationBuilder instanceof TYPO3\FLOW3\Property\PropertyMappingConfigurationBuilder
	$propertyMappingConfiguration = $propertyMappingConfigurationBuilder->build();
	// modify $propertyMappingConfiguration

	$propertyMapper->convert($source, $targetType, $propertyMappingConfiguration);

The following configuration options exist:

* ``setMapping($sourcePropertyName, $targetPropertyName)`` can be used to rename properties.

  Example: If the input array contains a property ``lastName``, but the accordant
  property in the model is called ``$givenName``, the following configuration performs
  the renaming::

    $propertyMappingConfiguration->setMapping('lastName', 'givenName');

* ``setTypeConverter($typeConverter)`` can be used to directly set a type converter
  which should be used. This disables the automatic resolving of type converters.

* ``setTypeConverterOption($typeConverterClassName, $optionKey, $optionValue)``
  can be used to set type converter specific options.

  Example: The DateTimeConverter supports a configuration option for the expected
  date format::

	$propertyMappingConfiguration->setTypeConverterOption(
		'TYPO3\FLOW3\Property\TypeConverter\DateTimeConverter',
		\TYPO3\FLOW3\Property\TypeConverter\DateTimeConverter::CONFIGURATION_DATE_FORMAT,
		'Y-m-d'
	);

* ``setTypeConverterOptions($typeConverterClassName, array $options)`` can be used
  to set multiple configuration options for the given ``TypeConverter``. This overrides
  all previously set configuration options for the ``TypeConverter``.

All the configuration options work only for the current level, i.e. all of the
above converter options would only work for the toplevel type converter. However,
it is also possible to specify configuration options for lower levels, using
``forProperty($propertyPath)``. This is best shown with the example from the previous section.

The following configuration sets a mapping on the top level, and furthermore
configures the ``DateTime`` converter for the birthDate property::

	$propertyMappingConfiguration->setMapping('fullName', 'name');
	$propertyMappingConfiguration
		->forProperty('birthDate')
		->setTypeConverterOption(
			'TYPO3\FLOW3\Property\TypeConverter\DateTimeConverter',
			\TYPO3\FLOW3\Property\TypeConverter\DateTimeConverter::CONFIGURATION_DATE_FORMAT,
			'Y-m-d'
		);

``forProperty()`` also supports more than one nesting level using the dot notation,
so writing something like ``forProperty('mother.birthDate')`` is possible.

Default Configuration - Security Considerations
-----------------------------------------------

The property mapping process can be security-relevant, as a small example should
show: Suppose there is a form where a person can create a new account, and assign
a role to this account (from a pre-defined list). This role controls the access
permissions the user has. The data which is sent to the server might look like this::

	array(
	  'username' => 'mynewuser',
	  'role' => '5bc42c89-a418-457f-8095-062ace6d22fd'
	);

Here, the ``username`` field contains the name of the user, and the ``role`` field points
to the role the user has selected. Now, an attacker could modify the data, and submit the
following::

	array(
	  'username' => 'mynewuser',
	  'role' => array(
	    'name' => 'superuser',
	    'admin' => 1
	  )
	);

As the property mapper works recursively, it would create a new ``Role`` object with the
admin flag set to ``TRUE``, which might compromise the security in the system.

That's why the PersistentObjectConverter has two options, ``CONFIGURATION_MODIFICATION_ALLOWED``
and ``CONFIGURATION_CREATION_ALLOWED``, which must be used to explicitely activate
the modification or creation of objects. By default, the ``PersistentObjectConverter``
does only fetch objects from the persistence, but does not create new ones or modifies
existing ones.

However, in the most-common use case, you want to use this magic functionality at
least for the top-level object which is being submitted. That's why the default
configuration (which is created by the ``PropertyMappingConfigurationBuilder``)
enables creation and modification for the top-level object, and disables it for
all sub objects by default.

Reference of TypeConverters
===========================

.. note:: TODO: should be automatically generated from the source

The Inner Workings of the Property Mapper
=========================================

The Property Mapper applies the following steps to convert a simple type to an
object. Some of the steps will be described in detail afterwards.

1. Figure out which type converter to use for the given source - target pair.

2. Ask this type converter to return the child properties of the source data
   (if it has any), by calling ``getSourceChildPropertiesToBeConverted()`` on
   the type converter.

3. For each child propery, do the following:

	1. Ask the type converter about the data type of the child property, by calling
	   ``getTypeOfChildProperty()`` on the type converter.

	2. Recursively invoke the ``PropertyMapper`` to build the child object from the input data.

4. Now, call the type converter again (method ``convertFrom()``), passing all (already
   built) child objects along. The result of this call is returned as the final result of the
   property mapping process.

On first sight, the steps might seem complex and difficult, but they account for
a great deal of flexibility of the property mapper.
Automatic resolving of type converters

Automatic Resolving of Type Converters
--------------------------------------

All type converters which implement ``TYPO3\FLOW3\Property\TypeConverterInterface``
are automatically found in the resolving process. There are four API methods in
each ``TypeConverter`` which influence the resolving process:

* ``getSupportedSourceTypes()``: returns an array of simple types which are
  understood as source type by this type converter

* ``getSupportedTargetType()``: The target type this type converter can convert
  into. Can be either a simple type, or a class name.

* ``getPriority()``: If two type converters have the same source and target type,
  precedence is given to the one with higher priority. All standard TypeConverters
  have a priority lower than 100.

* ``canConvertFrom($source, $targetType)`` is called as last check, when source
  and target types fit together. Here, the TypeConverter can implement runtime
  constraints to decide whether it can do the conversion.

When a type converter has to be found, the following algorithm is applied:

1. If typeConverter is set in the ``PropertyMappingConfiguration``, this is directly used.

2. The inheritance hierarchy of the target type is traversed in reverse order (from
   most specific to generic) until a TypeConverter is found. If two type converters
   work on the same class, the one with highest priority is used.

3. If no type converter could be found for the direct inheritance hierarchy, it is
   checked if there is a TypeConverter for one of the interfaces the target class
   implements. As it is not possible in PHP to order interfaces in any meaningful
   way, the TypeConverter with the highest priority is used (througout all interfaces).

4. If no type converter is found in the interfaces, it is checked if there is an
   applicable type converter for the target type ``object``.

If a type converter is found according to the above algorithm, ``canConvertFrom`` is
called on the type converter, so he can perform additional runtime checks. In case
the ``TypeConverter`` returns ``FALSE``, the search is continued at the position
where it left off in the above algorithm.

For simple target types, the steps 2 and 3 are omitted.

Writing Own TypeConverters
--------------------------

Often, it is enough to subclass
``TYPO3\FLOW3\Property\TypeConverter\AbstractTypeConverter``
instead of implementing ``TypeConverterInterface``.

Besides, good starting points for own type converters are the ``DateTimeConverter``
or the ``IntegerConverter``. If you write your own type converter, you should set
it to a priority greater than 100, to make sure it is used before the standard
converters by FLOW3.

TypeConverters should not contain any internal state, as they are re-used by the
property mapper, even recursively during the same run.

Of further importance is the exception and error semantics, so there are a few
possibilities what can be returned in ``convertFrom()``:

* For fatal errors which hint at some wrong configuration of the developer, throw
  an exception. This will show a stack trace in development context. Also for
  detected security breaches, exceptions should be thrown.

* If at run-time the type converter does not wish to participate in the results,
  ``NULL`` should be returned. For example, if a file upload is expected, but there
  was no file uploaded, returning ``NULL`` would be the appropriate way to handling
  this.

* If the error is recoverable, and the user should re-submit his data, return a
  ``TYPO3\FLOW3\Error\Error`` object (or a subclass thereof), containing information
  about the error. In this case, the property is not mapped at all (``NULL`` is
  returned, like above).

  If the Property Mapping occurs in the context of the MVC stack (as it will be the
  case in most cases), the error is detected and a forward is done to the last shown
  form. The end-user experiences the same flow as when MVC validation errors happen.

  This is the correct response for example if the file upload could not be processed
  because of wrong checksums, or because the disk on the server is full.

.. warning::
	Inside a type converter it is not allowed to use an (injected)
	instance of ``TYPO3\FLOW3\Property\PropertyMapper`` because it can lead to
	an infinite, recursive invocation.
