<?php
namespace TYPO3\Flow\Mvc\View;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * The not found view - a special case.
 *
 * @deprecated since Flow 2.0. Use the "renderingGroups" options of the exception handler configuration instead
 */
class NotFoundView extends \TYPO3\Flow\Mvc\View\AbstractView {

	/**
	 * @var \TYPO3\Flow\Mvc\Controller\ControllerContext
	 */
	protected $controllerContext;

	/**
	 * @var array
	 */
	protected $variablesMarker = array('errorMessage' => 'ERROR_MESSAGE');

	/**
	 * Renders the not found view
	 *
	 * @return string The rendered view
	 * @throws \TYPO3\Flow\Mvc\Exception if no request has been set
	 * @api
	 */
	public function render() {
		if (!is_object($this->controllerContext->getRequest())) {
			throw new \TYPO3\Flow\Mvc\Exception('Can\'t render view without request object.', 1192450280);
		}

		$template = file_get_contents($this->getTemplatePathAndFilename());
		$template = str_replace('{BASEURI}', $this->controllerContext->getRequest()->getHttpRequest()->getBaseUri(), $template);

		foreach ($this->variablesMarker as $variableName => $marker) {
			$variableValue = isset($this->variables[$variableName]) ? $this->variables[$variableName] : '';
			$template = str_replace('{' . $marker . '}', $variableValue, $template);
		}

		$this->controllerContext->getResponse()->setStatus(404);

		return $template;
	}

	/**
	 * Retrieves path and filename of the not-found-template
	 *
	 * @return string path and filename of the not-found-template
	 */
	protected function getTemplatePathAndFilename() {
		return FLOW_PATH_FLOW . 'Resources/Private/Mvc/NotFoundView_Template.html';
	}

	/**
	 * A magic call method.
	 *
	 * Because this not found view is used as a Special Case in situations,
	 * it must be able to handle method calls which originally were
	 * directed to another type of view. This magic method should prevent PHP from issuing
	 * a fatal error.
	 *
	 * @param string $methodName Name of the method
	 * @param array $arguments Arguments passed to the method
	 * @return void
	 */
	public function __call($methodName, array $arguments) {
	}
}

?>