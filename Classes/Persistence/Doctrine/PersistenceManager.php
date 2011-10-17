<?php
namespace TYPO3\FLOW3\Persistence\Doctrine;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * FLOW3's Doctrine PersistenceManager
 *
 * @FLOW3\Scope("singleton")
 * @api
 */
class PersistenceManager extends \TYPO3\FLOW3\Persistence\AbstractPersistenceManager {

	/**
	 * @var \Doctrine\Common\Persistence\ObjectManager
	 */
	protected $entityManager;

	/**
	 * @param \Doctrine\Common\Persistence\ObjectManager $entityManager
	 * @return void
	 */
	public function injectEntityManager(\Doctrine\Common\Persistence\ObjectManager $entityManager) {
		$this->entityManager = $entityManager;
	}

	/**
	 * Initializes the persistence manager, called by FLOW3.
	 *
	 * @return void
	 */
	public function initialize() {}

	/**
	 * Commits new objects and changes to objects in the current persistence
	 * session into the backend
	 *
	 * @return void
	 * @api
	 */
	public function persistAll() {
		$this->entityManager->flush();
		$this->emitAllObjectsPersisted();
	}

	/**
	 * Checks if the given object has ever been persisted.
	 *
	 * @param object $object The object to check
	 * @return boolean TRUE if the object is new, FALSE if the object exists in the repository
	 * @api
	 */
	public function isNewObject($object) {
		return ($this->entityManager->getUnitOfWork()->getEntityState($object, \Doctrine\ORM\UnitOfWork::STATE_NEW) === \Doctrine\ORM\UnitOfWork::STATE_NEW);
	}

	/**
	 * Returns the (internal) identifier for the object, if it is known to the
	 * backend. Otherwise NULL is returned.
	 *
	 * Note: this returns an identifier even if the object has not been
	 * persisted in case of AOP-managed entities. Use isNewObject() if you need
	 * to distinguish those cases.
	 *
	 * @param object $object
	 * @return mixed The identifier for the object if it is known, or NULL
	 * @api
	 * @todo improve try/catch block
	 */
	public function getIdentifierByObject($object) {
		if ($this->entityManager->contains($object)) {
			try {
				return current($this->entityManager->getUnitOfWork()->getEntityIdentifier($object));
			} catch (\Doctrine\ORM\ORMException $e) {
				return NULL;
			}
		} elseif (property_exists($object, 'FLOW3_Persistence_Identifier')) {
			return \TYPO3\FLOW3\Reflection\ObjectAccess::getProperty($object, 'FLOW3_Persistence_Identifier', TRUE);
		} else {
			return NULL;
		}
	}

	/**
	 * Returns the object with the (internal) identifier, if it is known to the
	 * backend. Otherwise NULL is returned.
	 *
	 * @param mixed $identifier
	 * @param string $objectType
	 * @param boolean $useLazyLoading Set to TRUE if you want to use lazy loading for this object
	 * @return object The object for the identifier if it is known, or NULL
	 * @throws \RuntimeException
	 * @api
	 */
	public function getObjectByIdentifier($identifier, $objectType = NULL, $useLazyLoading = FALSE) {
		if ($objectType === NULL) {
			throw new \RuntimeException('Using only the identifier is not supported by Doctrine 2. Give classname as well or use repository to query identifier.', 1296646103);
		}
		if ($useLazyLoading === TRUE) {
			return $this->entityManager->getReference($objectType, $identifier);
		} else {
			return $this->entityManager->find($objectType, $identifier);
		}
	}

	/**
	 * Return a query object for the given type.
	 *
	 * @param string $type
	 * @return \TYPO3\FLOW3\Persistence\Doctrine\Query
	 */
	public function createQueryForType($type) {
		return new \TYPO3\FLOW3\Persistence\Doctrine\Query($type);
	}

	/**
	 * Adds an object to the persistence.
	 *
	 * @param object $object The object to add
	 * @return void
	 * @api
	 */
	public function add($object) {
		$this->entityManager->persist($object);
	}

	/**
	 * Removes an object to the persistence.
	 *
	 * @param object $object The object to remove
	 * @return void
	 * @api
	 */
	public function remove($object) {
		$this->entityManager->remove($object);
	}

	/**
	 * Update an object in the persistence.
	 *
	 * @param object $object The modified object
	 * @return void
	 * @throws \TYPO3\FLOW3\Persistence\Exception\UnknownObjectException
	 * @throws \TYPO3\FLOW3\Persistence\Exception
	 * @api
	 */
	public function update($object) {
		if ($this->isNewObject($object)) {
			throw new \TYPO3\FLOW3\Persistence\Exception\UnknownObjectException('The object of type "' . get_class($object) . '" given to update must be persisted already, but is new.', 1313663277);
		}
		try {
			$this->entityManager->persist($object);
		} catch (\Exception $exception) {
			throw new \TYPO3\FLOW3\Persistence\Exception('Could not merge object of type "' . get_class($object) . '"', 1297778180, $exception);
		}
	}

	/**
	 * Called from functional tests, creates/updates database tables and compiles proxies.
	 *
	 * @return boolean
	 */
	public function compile() {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['path'] !== NULL) {
			$schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
			if ($this->settings['backendOptions']['driver'] === 'pdo_sqlite') {
				$schemaTool->createSchema($this->entityManager->getMetadataFactory()->getAllMetadata());
			} else {
				$schemaTool->updateSchema($this->entityManager->getMetadataFactory()->getAllMetadata());
			}

			$proxyFactory = $this->entityManager->getProxyFactory();
			$proxyFactory->generateProxyClasses($this->entityManager->getMetadataFactory()->getAllMetadata());

			$this->systemLogger->log('Doctrine 2 setup finished');
			return TRUE;
		} else {
			$this->systemLogger->log('Doctrine 2 setup skipped, driver and path backend options not set!', LOG_NOTICE);
			return FALSE;
		}
	}

	/**
	 * Called after a functional test in FLOW3, dumps everything in the database.
	 *
	 * @return void
	 */
	public function tearDown() {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['path'] !== NULL) {
			$this->entityManager->clear();

			$schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
			$schemaTool->dropDatabase();
			$this->systemLogger->log('Doctrine 2 schema destroyed.', LOG_NOTICE);
		} else {
			$this->systemLogger->log('Doctrine 2 destroy skipped, driver and path backend options not set!', LOG_NOTICE);
		}
	}

	/**
	 * Signals that all persistAll() has been executed successfully.
	 *
	 * @FLOW3\Signal
	 * @return void
	 */
	protected function emitAllObjectsPersisted() {
	}

}

?>