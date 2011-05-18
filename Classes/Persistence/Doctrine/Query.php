<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence\Doctrine;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A Query class for Doctrine 2
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class Query implements \F3\FLOW3\Persistence\QueryInterface {

	/**
	 * @var string
	 */
	protected $entityClassName;

	/**
	 * @var \Doctrine\ORM\QueryBuilder
	 */
	protected $queryBuilder;

	/**
	 * @var mixed
	 */
	protected $constraint;

	/**
	 * @var array
	 */
	protected $orderings;

	/**
	 * @var integer
	 */
	private $parameterIndex = 1;

	/**
	 * @param string $entityClassName
	 * @param \Doctrine\ORM\EntityManager $entityManager
	 */
	public function __construct($entityClassName, \Doctrine\ORM\EntityManager $entityManager) {
		$this->entityClassName = $entityClassName;
		$this->queryBuilder = $entityManager->createQueryBuilder()->select('e')->from($this->entityClassName, 'e');
	}

	/**
	 * Returns the type this query cares for.
	 *
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function getType() {
		return $this->entityClassName;
	}

	/**
	 * Executes the query and returns the result.
	 *
	 * @return \F3\FLOW3\Persistence\QueryResultInterface The query result
	 * @api
	 * @todo improve try/catch block
	 */
	public function execute() {
		try {
			return new \F3\FLOW3\Persistence\Doctrine\QueryResult($this->queryBuilder->getQuery()->getResult(), $this);
		} catch (\Doctrine\ORM\ORMException $e) {
			return new \F3\FLOW3\Persistence\Doctrine\QueryResult(array(), $this);
		}
	}

	/**
	 * Returns the query result count
	 *
	 * @return integer The query result count
	 * @api
	 * @todo improve try/catch block
	 */
	public function count() {
		try {
			$dqlQuery = clone $this->queryBuilder->getQuery();
			$dqlQuery->setHint(\Doctrine\ORM\Query::HINT_CUSTOM_TREE_WALKERS, array('F3\FLOW3\Persistence\Doctrine\CountWalker'));
			return (int)$dqlQuery->getSingleScalarResult();
		} catch (\Doctrine\ORM\ORMException $e) {
			return 0;
		}
	}

	/**
	 * Sets the property names to order the result by. Expected like this:
	 * array(
	 *  'foo' => \F3\FLOW3\Persistence\QueryInterface::ORDER_ASCENDING,
	 *  'bar' => \F3\FLOW3\Persistence\QueryInterface::ORDER_DESCENDING
	 * )
	 *
	 * @param array $orderings The property names to order by
	 * @return \F3\FLOW3\Persistence\QueryInterface
	 * @api
	 */
	public function setOrderings(array $orderings) {
		$this->queryBuilder->resetDQLPart('orderBy');
		$this->orderings = $orderings;
		foreach ($this->orderings AS $propertyName => $order) {
			$this->queryBuilder->addOrderBy($this->queryBuilder->getRootAlias() . '.' . $propertyName, $order);
		}
		return $this;
	}

	/**
	 * Returns the property names to order the result by, like this:
	 * array(
	 *  'foo' => \F3\FLOW3\Persistence\QueryInterface::ORDER_ASCENDING,
	 *  'bar' => \F3\FLOW3\Persistence\QueryInterface::ORDER_DESCENDING
	 * )
	 *
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function getOrderings() {
		return $this->orderings;
	}

	/**
	 * Sets the maximum size of the result set to limit. Returns $this to allow
	 * for chaining (fluid interface)
	 *
	 * @param integer $limit
	 * @return \F3\FLOW3\Persistence\QueryInterface
	 * @api
	 */
	public function setLimit($limit) {
		$this->queryBuilder->setMaxResults($limit);
		return $this;
	}

	/**
	 * Returns the maximum size of the result set to limit.
	 *
	 * @return integer
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function getLimit() {
		$this->queryBuilder->getMaxResults();
	}

	/**
	 * Sets the start offset of the result set to offset. Returns $this to
	 * allow for chaining (fluid interface)
	 *
	 * @param integer $offset
	 * @return \F3\FLOW3\Persistence\QueryInterface
	 * @api
	 */
	public function setOffset($offset) {
		$this->queryBuilder->setFirstResult($offset);
		return $this;
	}

	/**
	 * Returns the start offset of the result set.
	 *
	 * @return integer
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function getOffset() {
		$this->queryBuilder->getFirstResult();
	}

	/**
	 * The constraint used to limit the result set. Returns $this to allow
	 * for chaining (fluid interface)
	 *
	 * @param object $constraint Some constraint, depending on the backend
	 * @return \F3\FLOW3\Persistence\QueryInterface
	 * @api
	 */
	public function matching($constraint) {
		$this->constraint = $constraint;
		$this->queryBuilder->where($constraint);
		return $this;
	}

	/**
	 * Gets the constraint for this query.
	 *
	 * @return \F3\FLOW3\Persistence\Generic\Qom\Constraint the constraint, or null if none
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	*/
	public function getConstraint() {
		return $this->constraint;
	}

	/**
	 * Performs a logical conjunction of the two given constraints. The method
	 * takes one or more contraints and concatenates them with a boolean AND.
	 * It also accepts a single array of constraints to be concatenated.
	 *
	 * @param mixed $constraint1 The first of multiple constraints or an array of constraints.
	 * @return object
	 * @api
	 */
	public function logicalAnd($constraint1) {
		if (is_array($constraint1)) {
			$constraints = $constraint1;
		} else {
			$constraints = func_get_args();
		}
		return call_user_func_array(array($this->queryBuilder->expr(), 'andX'), $constraints);
	}

	/**
	 * Performs a logical disjunction of the two given constraints. The method
	 * takes one or more contraints and concatenates them with a boolean OR.
	 * It also accepts a single array of constraints to be concatenated.
	 *
	 * @param mixed $constraint1 The first of multiple constraints or an array of constraints.
	 * @return object
	 * @api
	 */
	public function logicalOr($constraint1) {
		if (is_array($constraint1)) {
			$constraints = $constraint1;
		} else {
			$constraints = func_get_args();
		}
		return call_user_func_array(array($this->queryBuilder->expr(), 'orX'), $constraints);
	}

	/**
	 * Performs a logical negation of the given constraint
	 *
	 * @param object $constraint Constraint to negate
	 * @return object
	 * @api
	 */
	public function logicalNot($constraint) {
		return $this->queryBuilder->expr()->not($constraint);
	}

	/**
	 * Returns an equals criterion used for matching objects against a query.
	 *
	 * It matches if the $operand equals the value of the property named
	 * $propertyName. If $operand is NULL a strict check for NULL is done. For
	 * strings the comparison can be done with or without case-sensitivity.
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @param boolean $caseSensitive Whether the equality test should be done case-sensitive for strings
	 * @return object
	 * @todo remove null handling as soon as supported natively by Doctrine
	 * @fixme implement case-sensitivity switch
	 * @api
	 */
	public function equals($propertyName, $operand, $caseSensitive = TRUE) {
		if ($operand === NULL) {
			return $this->getPropertyNameWithAlias($propertyName) . ' IS NULL';
		} else {
			return $this->queryBuilder->expr()->eq($this->getPropertyNameWithAlias($propertyName), $this->getParamNeedle($operand));
		}
	}

	/**
	 * Returns a like criterion used for matching objects against a query.
	 * Matches if the property named $propertyName is like the $operand, using
	 * standard SQL wildcards.
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param string $operand The value to compare with
	 * @param boolean $caseSensitive Whether the matching should be done case-sensitive
	 * @return object
	 * @throws \F3\FLOW3\Persistence\Exception\InvalidQueryException if used on a non-string property
	 * @todo implement case-sensitivity switch
	 * @api
	 */
	public function like($propertyName, $operand, $caseSensitive = TRUE) {
		return $this->queryBuilder->expr()->like($this->getPropertyNameWithAlias($propertyName), $this->getParamNeedle($operand));
	}

	/**
	 * Returns a "contains" criterion used for matching objects against a query.
	 * It matches if the multivalued property contains the given operand.
	 *
	 * If NULL is given as $operand, there will never be a match!
	 *
	 * @param string $propertyName The name of the multivalued property to compare against
	 * @param mixed $operand The value to compare with
	 * @return object
	 * @throws \F3\FLOW3\Persistence\Exception\InvalidQueryException if used on a single-valued property
	 * @api
	 */
	public function contains($propertyName, $operand) {
		return '(' . $this->getParamNeedle($operand) . ' MEMBER OF ' . $this->getPropertyNameWithAlias($propertyName) . ')';
	}

	/**
	 * Returns an "isEmpty" criterion used for matching objects against a query.
	 * It matches if the multivalued property contains no values or is NULL.
	 *
	 * @param string $propertyName The name of the multivalued property to compare against
	 * @return boolean
	 * @throws \F3\FLOW3\Persistence\Exception\InvalidQueryException if used on a single-valued property
	 * @api
	 */
	public function isEmpty($propertyName) {
		return '(' . $this->getPropertyNameWithAlias($propertyName) . ' IS EMPTY)';
	}

	/**
	 * Returns an "in" criterion used for matching objects against a query. It
	 * matches if the property's value is contained in the multivalued operand.
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with, multivalued
	 * @return object
	 * @throws \F3\FLOW3\Persistence\Exception\InvalidQueryException if used on a multi-valued property
	 * @api
	 */
	public function in($propertyName, $operand) {
		// Take care: In cannot be needled at the moment! DQL escapes it, but only as literals, making caching a bit harder.
		// This is a todo for Doctrine 2.1
		return $this->queryBuilder->expr()->in($this->getPropertyNameWithAlias($propertyName), $operand);
	}

	/**
	 * Returns a less than criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return object
	 * @throws \F3\FLOW3\Persistence\Exception\InvalidQueryException if used on a multi-valued property or with a non-literal/non-DateTime operand
	 * @api
	 */
	public function lessThan($propertyName, $operand) {
		return $this->queryBuilder->expr()->lt($this->getPropertyNameWithAlias($propertyName), $this->getParamNeedle($operand));
	}

	/**
	 * Returns a less or equal than criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return object
	 * @throws \F3\FLOW3\Persistence\Exception\InvalidQueryException if used on a multi-valued property or with a non-literal/non-DateTime operand
	 * @api
	 */
	public function lessThanOrEqual($propertyName, $operand) {
		return $this->queryBuilder->expr()->lte($this->getPropertyNameWithAlias($propertyName), $this->getParamNeedle($operand));
	}

	/**
	 * Returns a greater than criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return object
	 * @throws \F3\FLOW3\Persistence\Exception\InvalidQueryException if used on a multi-valued property or with a non-literal/non-DateTime operand
	 * @api
	 */
	public function greaterThan($propertyName, $operand) {
		return $this->queryBuilder->expr()->gt($this->getPropertyNameWithAlias($propertyName), $this->getParamNeedle($operand));
	}

	/**
	 * Returns a greater than or equal criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return object
	 * @throws \F3\FLOW3\Persistence\Exception\InvalidQueryException if used on a multi-valued property or with a non-literal/non-DateTime operand
	 * @api
	 */
	public function greaterThanOrEqual($propertyName, $operand) {
		return $this->queryBuilder->expr()->gte($this->getPropertyNameWithAlias($propertyName), $this->getParamNeedle($operand));
	}

	/**
	 * Get a needle for parameter binding.
	 *
	 * @param mixed $operand
	 * @return string
	 */
	protected function getParamNeedle($operand) {
		$index = $this->parameterIndex++;
		$this->queryBuilder->setParameter($index, $operand);
		return '?' . $index;
	}

	/**
	 * Adds left join clauses along the given property path to the query, if needed.
	 * This enables us to set conditions on related objects.
	 *
	 * @param string $propertyPath The path to a sub property, e.g. property.subProperty.foo, or a simple property name
	 * @return string The last part of the property name prefixed by the used join alias, if joins have been added
	 */
	protected function getPropertyNameWithAlias($propertyPath) {
		if (strpos($propertyPath, '.') === FALSE) {
			return $this->queryBuilder->getRootAlias() . '.' . $propertyPath;
		}

		$propertyPathParts = explode('.', $propertyPath);
		$conditionPartsCount = count($propertyPathParts);
		$previousJoinAlias = $this->queryBuilder->getRootAlias();
		for ($i = 0; $i < $conditionPartsCount - 1; $i++) {
			$joinAlias = uniqid($propertyPathParts[$i]);
			$this->queryBuilder->leftJoin($previousJoinAlias . '.' . $propertyPathParts[$i], $joinAlias);
			$previousJoinAlias = $joinAlias;
		}

		return $previousJoinAlias . '.' . $propertyPathParts[$i];
	}
}

?>