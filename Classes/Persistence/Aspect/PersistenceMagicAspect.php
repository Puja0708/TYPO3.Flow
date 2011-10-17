<?php
namespace TYPO3\FLOW3\Persistence\Aspect;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Adds the aspect of persistence magic to relevant objects
 *
 * @FLOW3\Aspect
 * @FLOW3\Introduce("TYPO3\FLOW3\Persistence\Aspect\PersistenceMagicAspect->isEntityOrValueObject", interfaceName="TYPO3\FLOW3\Persistence\Aspect\PersistenceMagicInterface")
 */
class PersistenceMagicAspect {

	/**
	 * If the extension "igbinary" is installed, use it for increased performance
	 *
	 * @var boolean
	 */
	protected $useIgBinary;

	/**
	 * @FLOW3\Pointcut("classTaggedWith(entity) || classTaggedWith(valueobject)")
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isEntityOrValueObject() {}

	/**
	 * @var string
	 * @ORM\Id
	 * @ORM\Column(length=40)
	 * @FLOW3\Introduce("TYPO3\FLOW3\Persistence\Aspect\PersistenceMagicAspect->isEntityOrValueObject && filter(TYPO3\FLOW3\Persistence\Doctrine\Mapping\Driver\Flow3AnnotationDriver)")
	 */
	protected $FLOW3_Persistence_Identifier;

	/**
	 * Initializes this aspect
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObject() {
		$this->useIgBinary = extension_loaded('igbinary');
	}

	/**
	 * After returning advice, making sure we have an UUID for each and every entity.
	 *
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint The current join point
	 * @return void
	 * @FLOW3\Before("classTaggedWith(entity) && method(.*->__construct())")
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function generateUUID(\TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$proxy = $joinPoint->getProxy();
		\TYPO3\FLOW3\Reflection\ObjectAccess::setProperty($proxy, 'FLOW3_Persistence_Identifier', \TYPO3\FLOW3\Utility\Algorithms::generateUUID(), TRUE);
	}

	/**
	 * After returning advice, generates the value hash for the object
	 *
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint The current join point
	 * @return void
	 * @FLOW3\Before("classTaggedWith(valueobject) && method(.*->__construct())")
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function generateValueHash(\TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$proxy = $joinPoint->getProxy();
		$hashSource = get_class($proxy);
		if (property_exists($proxy, 'FLOW3_Persistence_Identifier')) {
			$hashSource .= \TYPO3\FLOW3\Reflection\ObjectAccess::getProperty($proxy, 'FLOW3_Persistence_Identifier', TRUE);
		}
		foreach ($joinPoint->getMethodArguments() as $argumentValue) {
			if (is_array($argumentValue)) {
				$hashSource .= ($this->useIgBinary === TRUE) ? igbinary_serialize($argumentValue) : serialize($argumentValue);
			} elseif (!is_object($argumentValue)) {
				$hashSource .= $argumentValue;
			} elseif (property_exists($argumentValue, 'FLOW3_Persistence_Identifier')) {
				$hashSource .= \TYPO3\FLOW3\Reflection\ObjectAccess::getProperty($argumentValue, 'FLOW3_Persistence_Identifier', TRUE);
			} elseif ($argumentValue instanceof \DateTime) {
				$hashSource .= $argumentValue->getTimestamp();
			}
		}
		$proxy = $joinPoint->getProxy();
		\TYPO3\FLOW3\Reflection\ObjectAccess::setProperty($proxy, 'FLOW3_Persistence_Identifier', sha1($hashSource), TRUE);
	}

	/**
	 * Mark object as cloned after cloning.
	 *
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return void
	 * @FLOW3\AfterReturning("TYPO3\FLOW3\Persistence\Aspect\PersistenceMagicAspect->isEntityOrValueObject && method(.*->__clone())")
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function cloneObject(\TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$joinPoint->getProxy()->FLOW3_Persistence_clone = TRUE;
	}

	/**
	 * Generate new UUID for cloned entity
	 *
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return void
	 * @FLOW3\AfterReturning("classTaggedWith(entity) && method(.*->__clone())")
	 * @author Christian Müller <christian.mueller@typo3.org>
	 */
	public function generateNewUuidForClone(\TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$proxy = $joinPoint->getProxy();
		\TYPO3\FLOW3\Reflection\ObjectAccess::setProperty($proxy, 'FLOW3_Persistence_Identifier', \TYPO3\FLOW3\Utility\Algorithms::generateUUID(), TRUE);
	}

}
?>
