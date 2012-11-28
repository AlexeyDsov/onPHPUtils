<?php
namespace Onphp\Utils;

class ClassNameControllerMapper
{
	private $classNames = [];
	private $objectMap = [];

	public function registerMap($mapOptions) {
		foreach ($mapOptions as $options) {
			$this->register($options);
		}
	}

	public function register($options)
	{
		\Onphp\Assert::isTrue(
			isset($options['class'], $options['controller'], $options['object'], $options['actions'], $options['helper'])
		);
		$className = $options['class'];
		$controllerName = $options['controller'];
		$objectName = $options['object'];

		foreach (explode(':', $options['actions']) as $action) {
			$this->classNames[$className][$controllerName][$action] = [$objectName, $options['helper']];
		}

		$this->objectMap[$objectName][$className.':'.$controllerName.':'.$options['helper']] = true;

		return $this;
	}

	public function getUrlParts($className, $action)
	{
		if (!isset($this->classNames[$className])) {
			return;
		}
		foreach ($this->classNames[$className] as $controllerName => $actions) {
			if (isset($this->classNames[$className][$controllerName][$action])) {
				$row = $this->classNames[$className][$controllerName][$action];
				return [
					'action' => $action,
					'object' => $row[0],
				];
			}
		}
	}

	public function findControllerAndHelper($objectName, $action)
	{
		if (!isset($this->objectMap[$objectName])) {
			return;
		}
		foreach (array_keys($this->objectMap[$objectName]) as $map) {
			list($className, $controllerName, $helper) = explode(':', $map);
			$actions = array_keys($this->classNames[$className][$controllerName]);
			if (in_array($action, $actions)) {
				return array($controllerName, $helper, $className);
			}
		}
	}
}
