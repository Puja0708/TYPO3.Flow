﻿================
Session Handling
================

TYPO3 Flow has excellent support for working with sessions.

This chapter will explain:

* ... how to store specific data in a session
* ... how to store objects in the session

Scope Session
=============

TYPO3 Flow does not only support the ``prototype`` and ``singleton`` object scopes, but also the
object scope ``session``. Objects marked like this basically behave like ``singleton`` objects
which are automatically serialized into the user's session.

As an example, when building a shopping basket, the class could look as follows::

	/**
	 * @Flow\Scope("session")
	 */
	class ShoppingBasket {

		/**
		 * @var array
		 */
		protected $items = array();

		/**
		 * @param string $item
		 * @return void
		 * @Flow\Session(autoStart = TRUE)
		 */
		public function addItem($item) {
			$this->items[] = $item;
		}

		/**
		 * @return array
		 */
		public function getItems() {
			return $this->items;
		}
	}

In the above example

* the object scope is set to ``session``, so it behaves like a *user-bound cross-request
  singleton*. This ``ShoppingBasket`` can now be injected where it is needed using *Dependency
  Injection*.
* We only want to start a session when the first element is added to the shopping basket.
  For this the addItem() method needs to be annotated with ``@Flow\Session(autoStart = TRUE)``.

When a user browses the website, the following then happens:

* First, the user's shopping basket is empty, and getItems() returns an empty array.
  No session exists yet. For each page being requested, the ``ShoppingBasket`` is
  newly initialized.

* As soon as the user adds something to the shopping basket, ``addItem()`` is called.
  Because this is annotated with ``@Flow\Session(autoStart = TRUE)``, a new PHP session
  is started, and the ShoppingBasket is placed into the session.

* As the user continues to browse the website, the ``ShoppingBasket`` is being fetched
  from the user's session (which exists now). Thus, ``getItems()`` returns the items
  from the session.


.. admonition:: Why is ``@Flow\Session(autoStart = TRUE)`` necessary?

	If TYPO3 Flow did not have this annotation, there would be no way for it to determine
	when a session must be started. Thus, every user browsing the website would
	*always* need a session as soon as an object of scope ``session`` is accessed.
	This would happens if the ``session``-scoped object is still in its initial state.

	To be able to use proxies such as Varnish, TYPO3 Flow defers the creation of a
	session to a point in time when it is really needed -- and the developer needs
	to tell the framework when that point is reached using the above annotation.


The TYPO3 Flow session scope handles persistent objects and dependency injection correctly:

* Objects which are injected via Dependency Injection are removed before serialization
  and re-injected on deserialization.

* Persistent objects which are unchanged are just stored as a reference and fetched
  from persistence again on deserialization.

* Persistent objects which are modified are fully stored in the session.

Low-level session handling
==========================

It is possible to inject the ``TYPO3\Flow\Session\SessionInterface`` and interact
with the PHP session on a low level, by using ``start()``, ``getData()`` and ``putData()``.

That should rarely be needed, though. Instead of manually serializing objects object into
the session, the *session scope* should be used whenever possible.

Flow Session
============

The session implementation written in plain PHP (FlowSession) uses the caching
framework as its storage. This allows for storing session data in a variety of
backends, including PDO databases, APC, Memcache and Redis.

In order to use FlowSession instead of PhpSession you need to select the
implementation through a custom Objects.yaml placed in one of your packages or the
global configuration:

.. code-block:: yaml

	TYPO3\Flow\Session\SessionInterface:
	  className: TYPO3\Flow\Session\FlowSession

Likewise, the preferred storage backend for FlowSession is defined through a custom
Caches.yaml file, placed in a package or the global configuration:

.. code-block:: yaml

	Flow_Session_Storage:
	  backend: TYPO3\Flow\Cache\Backend\ApcBackend

The FlowSession implementation provides a few more configuration options, related to
the session cookie and the automatic garbage collection. Please refer to the
Settings.yaml file of the TYPO3 Flow package for a list of all possible options and
their respective documentation.