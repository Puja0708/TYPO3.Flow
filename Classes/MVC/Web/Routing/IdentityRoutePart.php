<?php
namespace TYPO3\FLOW3\MVC\Web\Routing;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Identity Route Part
 * This route part can be used to create and resolve ObjectPathMappings.
 * This handler is used by default, if an objectType is specified for a route part in the routing configuration:
 * -
 *   name: 'Some route for xyz entities'
 *   uriPattern: '{xyz}'
 *   routeParts:
 *     xyz:
 *       objectType: Some\Package\Domain\Model\Xyz
 *
 * @see \TYPO3\FLOW3\MVC\Web\Routing\ObjectPathMapping
 * @api
 */
class IdentityRoutePart extends \TYPO3\FLOW3\MVC\Web\Routing\DynamicRoutePart {

	/**
	 * @var \TYPO3\FLOW3\Persistence\PersistenceManagerInterface
	 * @inject
	 */
	protected $persistenceManager;

	/**
	 * @var \TYPO3\FLOW3\Reflection\ReflectionService
	 * @inject
	 */
	protected $reflectionService;

	/**
	 * @var \TYPO3\FLOW3\MVC\Web\Routing\ObjectPathMappingRepository
	 * @inject
	 */
	protected $objectPathMappingRepository;

	/**
	 * The object type (class name) of the entity this route part belongs to
	 *
	 * @var string
	 */
	protected $objectType;

	/**
	 * pattern for the URI representation (for example "{date:Y}/{date:m}/{date.d}/{title}")
	 *
	 * @var string
	 */
	protected $uriPattern = NULL;

	/**
	 * @param string $objectType
	 */
	public function setObjectType($objectType) {
		$this->objectType = $objectType;
	}

	/**
	 * @param return string
	 */
	public function getObjectType() {
		return $this->objectType;
	}

	/**
	 * @param string $uriPattern
	 */
	public function setUriPattern($uriPattern) {
		$this->uriPattern = $uriPattern;
	}

	/**
	 * If $this->uriPattern is specified, this will be returned, otherwise identity properties of $this->objectType
	 * are returned in the format {property1}/{property2}/{property3}.
	 * If $this->objectType does not contain identity properties, an empty string is returned.
	 *
	 * @return string
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getUriPattern() {
		if ($this->uriPattern === NULL) {
			$classSchema = $this->reflectionService->getClassSchema($this->objectType);
			$identityProperties = $classSchema->getIdentityProperties();
			if (count($identityProperties) === 0) {
				$this->uriPattern = '';
			} else {
				$this->uriPattern = '{' . implode('}/{', array_keys($identityProperties)) . '}';
			}
		}
		return $this->uriPattern;
	}

	/**
	 * Checks, whether given value can be matched.
	 * If the value is empty, FALSE is returned.
	 * Otherwise the ObjectPathMappingRepository is asked for a matching ObjectPathMapping.
	 * If that is found the identifier is stored in $this->value, otherwise this route part does not match.
	 *
	 * @param string $value value to match
	 * @return boolean TRUE if value could be matched successfully, otherwise FALSE.
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	protected function matchValue($value) {
		if ($value === NULL || $value === '') {
			return FALSE;
		}
		$objectPathMapping = $this->objectPathMappingRepository->findOneByObjectTypeUriPatternAndPathSegment($this->objectType, $this->getUriPattern(), $value);
		if ($objectPathMapping === NULL) {
			return FALSE;
		}
		$this->value = $objectPathMapping->getIdentifier();
		return TRUE;
	}

	/**
	 * Returns the first part of $routePath that should be evaluated in matchValue().
	 * If not split string is set (route part is the last in the routes uriPattern), the complete $routePart is returned.
	 * Otherwise the part is returned that matches the specified uriPattern of this route part.
	 *
	 * @param string $routePath The request path to be matched
	 * @return string value to match, or an empty string if $routePath is empty, split string was not found or uriPattern could not be matched
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	protected function findValueToMatch($routePath) {
		if (!isset($routePath) || $routePath === '' || $routePath[0] === '/') {
			return '';
		}
		$valueToMatch = $routePath;
		if ($this->splitString === '') {
			return $valueToMatch;
		}
		$splitStringPosition = strpos($valueToMatch, $this->splitString);
		if ($splitStringPosition !== FALSE) {
			$valueToMatch = substr($valueToMatch, 0, $splitStringPosition);
		}
		$uriPattern = $this->getUriPattern();
		if ($uriPattern === '') {
			return $valueToMatch;
		}
		$regexPattern = preg_replace('/{[^}]+}/', '[^\/]+', str_replace('/', '\/', $uriPattern));
		$matches = array();
		preg_match('/^' . $regexPattern . '$/', trim($valueToMatch, '/'), $matches);
		return isset($matches[0]) ? $matches[0] : '';
	}

	/**
	 * Resolves the given entity and sets the value to a URI representation (path segment) that matches $this->uriPattern and is unique for the given object.
	 *
	 * @param mixed $value
	 * @return boolean TRUE if the object could be resolved and stored in $this->value, otherwise FALSE.
	 * @throws \TYPO3\FLOW3\MVC\Exception\InfiniteLoopException if no unique path segment could be found after 100 iterations
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function resolveValue($value) {
		if (!$value instanceof $this->objectType) {
			return FALSE;
		}
		$identifier = $this->persistenceManager->getIdentifierByObject($value);
		$objectPathMapping = $this->objectPathMappingRepository->findOneByObjectTypeUriPatternAndIdentifier($this->objectType, $this->getUriPattern(), $identifier);
		if ($objectPathMapping !== NULL) {
			$this->value = $objectPathMapping->getPathSegment();
			return TRUE;
		}
		$pathSegment = $uniquePathSegment = $this->createPathSegmentForObject($value);
		$pathSegmentLoopCount = 0;
		do {
			if ($pathSegmentLoopCount++ > 99) {
				throw new \TYPO3\FLOW3\MVC\Exception\InfiniteLoopException('No unique path segment could be found after ' . ($pathSegmentLoopCount - 1) . ' iterations.', 1316441798);
			}
			$objectPathMapping = $this->objectPathMappingRepository->findOneByObjectTypeUriPatternAndPathSegment($this->objectType, $this->getUriPattern(), $uniquePathSegment);
			if ($objectPathMapping === NULL) {
				$this->storeObjectPathMapping($uniquePathSegment, $identifier);
				break;
			}
			$uniquePathSegment = sprintf('%s-%d', $pathSegment, $pathSegmentLoopCount);
		} while (TRUE);

		$this->value = $uniquePathSegment;
		return TRUE;
	}

	/**
	 * Creates a URI representation (path segment) for the given object matching $this->uriPattern.
	 *
	 * @param mixed $object object of type $this->objectType
	 * @return string URI representation (path segment) of the given object
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function createPathSegmentForObject($object) {
		$uriPattern = $this->getUriPattern();
		if ($uriPattern === '') {
			return $this->rewriteForUri($this->persistenceManager->getIdentifierByObject($object));
		}
		$matches = array();
		preg_match_all('/(?P<dynamic>{?)(?P<content>[^}{]+)}?/', $uriPattern, $matches, PREG_SET_ORDER);
		$pathSegment = '';
		foreach ($matches as $match) {
			if (empty($match['dynamic'])) {
				$pathSegment .= $match['content'];
			} else {
				$dynamicPathSegmentParts = explode(':', $match['content']);
				$propertyPath = $dynamicPathSegmentParts[0];
				$dynamicPathSegment = \TYPO3\FLOW3\Reflection\ObjectAccess::getPropertyPath($object, $propertyPath);
				if (is_object($dynamicPathSegment)) {
					if ($dynamicPathSegment instanceof \DateTime) {
						$dateFormat = isset($dynamicPathSegmentParts[1]) ? trim($dynamicPathSegmentParts[1]) : 'Y-m-d';
						$pathSegment .= $this->rewriteForUri($dynamicPathSegment->format($dateFormat));
					} else {
						throw new \TYPO3\FLOW3\MVC\Exception\InvalidUriPatternException('Invalid uriPattern "' . $uriPattern . '" for route part "' . $this->getName() . '". Property "' . $propertyPath . '" must be of type string or \DateTime. "' . (is_object($dynamicPathSegment) ? get_class($dynamicPathSegment) : gettype($dynamicPathSegment)) . '" given.', 1316442409);
					}
				} else {
					$pathSegment .= $this->rewriteForUri($dynamicPathSegment);
				}
			}
		}
		return $pathSegment;
	}

	/**
	 * Creates a new ObjectPathMapping and stores it in the repository
	 *
	 * @var string $pathSegment
	 * @var mixed $identifier
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function storeObjectPathMapping($pathSegment, $identifier) {
		$objectPathMapping = new \TYPO3\FLOW3\MVC\Web\Routing\ObjectPathMapping();
		$objectPathMapping->setObjectType($this->objectType);
		$objectPathMapping->setUriPattern($this->getUriPattern());
		$objectPathMapping->setPathSegment($pathSegment);
		$objectPathMapping->setIdentifier($identifier);
		$this->objectPathMappingRepository->add($objectPathMapping);
		// TODO can be removed, when persistence manager has some memory cache
		$this->persistenceManager->persistAll();
	}

	/**
	 * Transforms the given string into a URI compatible format without special characters.
	 * In the long term this should be done with proper transliteration
	 *
	 * @param string $value
	 * @return string
	 * @todo use transliteration of the L18n sub package
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function rewriteForUri($value) {
		$transliteration = array(
			'ä' => 'ae',
			'Ä' => 'Ae',
			'ö' => 'oe',
			'Ö' => 'Oe',
			'ü' => 'ue',
			'Ü' => 'Ue',
			'ß' => 'ss',
		);
		$value = strtr($value, $transliteration);

		$spaceCharacter = '-';
		$value = preg_replace('/[ \-+_]+/', $spaceCharacter, $value);

		$value = preg_replace('/[^-a-z0-9.\\' . $spaceCharacter . ']/i', '', $value);

		$value = preg_replace('/\\' . $spaceCharacter . '{2,}/', $spaceCharacter, $value);
		$value = trim($value, $spaceCharacter);

		return $value;
	}


}
?>