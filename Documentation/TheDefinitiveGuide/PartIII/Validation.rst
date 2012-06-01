﻿==========
Validation
==========

Validation in web applications is a very crucial topic: Almost all data which is entered by
an end user needs some checking rules, no matter if he enters an e-mail address or a subject
for a forum posting.

While validation itself is quite simple, embedding it into the rest of the framework is not:
If the user has entered a wrong value, the original page has to be re-displayed, and the user
needs some well-readable information on what data he should enter.

This chapter will explain:

* ... how to use the validators being part of FLOW3
* ... how to write your own validators
* ... how to use validation in your own code
* ... how validation is embedded in the model, the persistence and the MVC layer


Using Validators & The ValidatorResolver
========================================

A validator is a PHP class being responsible for checking validity of a certain object or
simple type.

All validators implement ``TYPO3\FLOW3\Validation\Validator\ValidatorInterface``, and
the API of every validator is demonstrated in the following code example::

	// NOTE: you should always use the ValidatorResolver to create new
	// validators, as it is demonstrated in the next section.
	$validator = new \TYPO3\FLOW3\Validation\Validator\StringLengthValidator(array(
		'minimum' => 10,
		'maximum' => 20
	));

	// $result is of type TYPO3\FLOW3\Error\Result
	$result = $validator->validate('myExampleString');
	$result->hasErrors(); // is FALSE, as the string is longer than 10 characters.

	$result = $validator->validate('short');
	$result->hasErrors(); // is TRUE, as the string is too short.
	$result->getFirstError()->getMessage(); // contains the human-readable error message

On the above example, it can be seen that validators can be *re-used* for different input.
Furthermore, a validator does not only just return TRUE or FALSE, but instead returns
a ``Result`` object which you can ask whether any errors happened. Please see the API
for a detailed description.

.. note:: The ``TYPO3\FLOW3\Error\Result`` object has been introduced in order to
	make more structured error output possible -- which is especially needed when
	objects with sub-properties should be validated recursively.

Creating Validator Instances: The ValidatorResolver
---------------------------------------------------

As validators can be both singleton or prototype objects (depending if they have internal state),
you should not instanciate them directly as it has been done in the above example. Instead,
you should use the ``TYPO3\FLOW3\Validation\ValidatorResolver`` singleton to get a new instance
of a certain validator::

	$validatorResolver->createValidator($validatorType, array $validatorOptions);

``$validatorType`` can be one of the following:

* a fully-qualified class name to a validator, like ``Your\Package\Validation\Validator\FooValidator``
* If you stick to the ``<PackageKey>\Validation\Validator\<ValidatorName>Validator`` convention,
  you can also fetch the above validator using ``Your.Package:Foo`` as ``$validatorType``.

  **This is the recommended way for custom validators.**
* For the standard validators inside the ``TYPO3.FLOW3`` package, you can leave out the package key,
  so you can use ``EmailAddress`` to fetch ``TYPO3\FLOW3\Validation\Validator\EmailAddressValidator``

The ``$validatorOptions`` parameter is an associative array of validator options. See the validator
reference in the appendix for the configuration options of the built-in validators.


Default Validators
------------------

FLOW3 is shipped with a big list of validators which are ready to use -- see the appendix for the full
list. Here, we just want to highlight some more special validators.

Additional to the simple validators for strings, numbers and other basic types, FLOW3 has a few powerful
validators shipped:

* ``GenericObjectValidator``: Validates an object by validating all of its properties. This validator
  is often used internally, but often you will not use it directly.
* ``CollectionValidator``: validate a collection of objects. This validator is often used internally,
  but often you will not use it directly.
* ``ConjunctionValidator`` and ``DisjunctionValidator``: Implement logical AND / OR conditions.

Furthermore, almost all validators of simple types regard ``NULL`` and the empty string (``""``) as **valid**.
The only exception is the ``NotEmpty`` validator, which disallows both ``NULL`` and empty string. This means
if you want to validate that a property is e.g. an E-Mail and does exist, you need to combine the two validators
using a ``ConjunctionValidator``::

	$conjunctionValidator = $validatorResolver->createValidator('Conjunction');
	$conjunctionValidator->addValidator($validatorResolver->createValidator('NotEmpty'));
	$conjunctionValidator->addValidator($validatorResolver->createValidator('EmailAddress'));

Writing Own Validators
======================

Usually, when you write an own validator, you will not directly implement ``ValidatorInterface``, but
rather subclass ``AbstractValidator``. You only need to implement the ``isValid()`` method then::

	class MySpecialValidator extends \TYPO3\FLOW3\Validation\Validator\AbstractValidator {
		protected function isValid($value) {
			if (!isset($this->options['foo'])) {
				throw new \TYPO3\FLOW3\Validation\Exception\InvalidValidationOptionsException(
					'The option "foo" for this validator needs to be specified', 12346788
				);
			}

			if ($value !== $this->options['foo']) {
				$this->addError('The value must be equal to "%s"', 435346321, array($this->options['foo']));
			}
		}
	}

In the above example, the ``isValid()`` method has been implemented, and the parameter ``$value`` is the
data we want to check for validity. In case the data is valid, nothing needs to be done.

In case the data is invalid, ``$this->addError()`` should be used to add an error message, an error code
(which should be the unix timestamp of the current time) and optional arguments which are inserted into
the error message.

The options of the validator can be accessed in the associative array ``$this->options``. In case any
of the options is invalid, an ``InvalidValidationOptionsException`` should be thrown.

.. tip:: Because you extended AbstractValidator in the above example, ``NULL`` and empty string
	are automatically regarded as valid values; as it is the case for all other validators.


* TODO Karsten: write something about custom Object Validator


Validating Domain Models
========================

It is very common that a full Domain Model should be validated instead of only a simple type.
To make this use-case more easy, the ``ValidatorResolver`` has a method ``getBaseValidatorConjunction``
which returns a fully-configured validator for an arbitrary Domain Object::

	$commentValidator = $validatorResolver->getBaseValidatorConjunction('YourPackage\Domain\Model\Comment`);
	$result = $commentValidator->validate($comment);

The returned validator checks the following things:

* All *property validation rules* configured through ``@FLOW3\Validate`` annotations on properties of the model:

	.. code-block:: php

		namespace YourPackage\Domain\Model;
		use TYPO3\FLOW3\Annotations as FLOW3;

		class Comment {

			/**
			 * @FLOW3\Validate(type="NotEmpty")
			 */
			protected $text;

			// Add getters and setters here
		}

	It also correctly builds up validators for ``Collections`` or ``arrays``, if they are properly
	typed (``Doctrine\Common\Collection<YourPackage\Domain\Model\Author>``).

* In addition to validating the individual properties on the model, it checks whether a designated *Domain Model
  Validator* exists; i.e. for the Domain Model ``YourPackage\Domain\Model\Comment`` it is checked
  whether ``YourPackage\Domain\Validator\CommentValidator`` exists. If it exists, it is automatically
  called on validation.

When specifying a Domain Model as an argument of a controller action, all the above validations will be
automatically executed. This is explained in detail in the following section.


Automatic Validation Throughout The Framework
=============================================

Inside FLOW3, validation is triggered automatically at two places: When an object is *persisted*, its
*base validators* are checked as explained in the last section. Furthermore, validation happens in
the MVC layer when a Domain Model is used as a controller argument, directly after Property Mapping.

.. warning:: If a validation error occurs during persistence, there is no way to catch this error
	and handle it -- as persistence is executed at the end of every request *after the response
	has been sent to the client*.

	Thus, validation on persistence is merely a safeguard for preventing invalid data to be stored
	in the database.

When validation in the MVC layer happens, it is possible to handle errors correctly. In a nutshell,
the process is as follows:

* an array of data is received from the client
* it is transformed to an object using Property Mapping
* this object is validated using the base validators
* if there is a property mapping or validation error, the last page (which usually contains an
  edit-form) is re-displayed, an error message is shown and the erroreous field is highlighted.

.. tip:: If you want to suppress the re-displaying of the last page (which is handled through
	``errorAction()``, you can add a ``@FLOW3\IgnoreValidation("comment")`` annotation
	to the docblock of the corresponding controller action.

Furthermore, it is also possible to execute *additional validators* only for specific action
arguments using ``@FLOW3\Validate`` inside a controller action::

	class CommentController extends \TYPO3\FLOW3\Mvc\Controller\ActionController {

		/**
		 * @param \YourPackage\Domain\Model\Comment $comment
		 * @FLOW3\Validate(argumentName="comment", type="YourPackage:SomeSpecialValidator")
		 */
		public function updateAction(\YourPackage\Domain\Model\Comment $comment) {
			// here, $comment is a valid object
		}
	}

.. tip:: It is also possible to add an additional validator for a sub object of the argument, using
	the "dot-notation": ``@FLOW3\Validate(argumentName="comment.text", type="....")``.

However, it is a rather rare use-case that a validation rule needs to be defined only in the controller.

Advanced Feature: Partial Validation
====================================

If you only want to validate parts of your objects, f.e. want to store incomplete objects in
the database, you can assign special *Validation Groups* to your validators.

It is possible to specify a list of validation groups at each ``@FLOW3\Validate`` annotation,
if none is specified the validation group ``Default`` is assigned to the validator.

When *invoking* validation, f.e. in the MVC layer or in persistence, only validators with
certain validation groups are executed:

* In MVC, the validation group ``Default`` and ``Controller`` is used.
* In persistence, the validation group ``Default`` and ``Persistence`` is used.

A validator is only executed if at least one validation group overlap.

The following example should demonstrate this::

	class Comment {
		/**
		 * @FLOW3\Validate(name='Required')
		 */
		protected $prop1;

		/**
		 * @FLOW3\Validate(name='Required', validationGroups={'Default')})
		 */
		protected $prop2;

		/**
		 * @FLOW3\Validate(name='Required', validationGroups={'Persistence')})
		 */
		protected $prop3;

		/**
		 * @FLOW3\Validate(name='Required', validationGroups={'Controller')})
		 */
		protected $prop3;
	}

* validation for prop1 and prop2 are the same, as the "Default" validation group is added if none is specified
* validation for prop1 and prop2 are executed both on persisting and inside the controller
* validation for $prop3 is only executed in persistence, but not in controller
* validation for $prop4 is only executed in controller, but not in persistence

If interacting with the ``ValidatorResolver`` directly, the to-be-used validation groups
can be specified as the last argument of ``getBaseValidatorConjunction()``.

Summary
=======

In this chapter, validation of simple types and objects was discussed. After demonstrating
how to use validators, it was shown how to write custom ones.

In the second part, validation in the context of Domain Models and the MVC framework was
introduced, giving an overview where and how validation is triggered inside the framework.