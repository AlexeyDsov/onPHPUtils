<?php
/**
 * Утилита для генерации url/имени диалогового окна на информацию/редактирование/логи объекта
 */
namespace Onphp\Utils;

class ToolkitLinkUtils implements IServiceLocatorSupport {
	use TServiceLocatorSupport;

	protected $logClassName = null;
	protected $baseUrl = null;
	/**
	 * @var IPermissionUser
	 */
	protected $user = null;
	/**
	 * @var ClassNameControllerMapper
	 */
	protected $classNameMapper = null;

	/**
	 * @return \Onphp\Utils\ToolkitLinkUtils
	 */
	public static function create()
	{
		return new self;
	}

	/**
	 * @param string $logClassName
	 * @return \Onphp\Utils\ToolkitLinkUtils
	 */
	public function setLogClassName($logClassName)
	{
		$this->logClassName = $logClassName;
		return $this;
	}

	/**
	 * @param string $baseUrl
	 * @return \Onphp\Utils\ToolkitLinkUtils
	 */
	public function setBaseUrl($baseUrl) {
		$this->baseUrl = $baseUrl;
		return $this;
	}

	/**
	 * @param IPermissionUser $user
	 * @return \Onphp\Utils\ToolkitLinkUtils
	 */
	public function setUser(IPermissionUser $user) {
		$this->user = $user;
		return $this;
	}

	/**
	 * @return ClassNameControllerMapper
	 */
	public function getClassNameMapper()
	{
		return $this->classNameMapper;
	}

	/**
	 * @param \Onphp\Utils\ClassNameControllerMapper $classNameMapper
	 * @return \Onphp\Utils\ToolkitLinkUtils
	 */
	public function setClassNameMapper(ClassNameControllerMapper $classNameMapper)
	{
		$this->classNameMapper = $classNameMapper;
		return $this;
	}

	/**
	 * Проверяет поддерживает ли эта утилита тип переданного объекта
	 * @param mixed $object
	 * @param string $method префикс действия пользователя, на которое проверяются у него права
	 * @return boolean
	 */
	public function isObjectSupported($object, $method)
	{
		return $this->getObjectPermission($object, $method)->isAllowed();
	}

	/**
	 * @param mixed $object
	 * @param string $method
	 * @return \Onphp\Utils\PermissionSimple
	 */
	public function getObjectPermission($object, $method)
	{
		if ($this->user) {
			return $this->getPermissionManager()->getPermission($this->user, $method, $object);
		}
		return new PermissionSimple(true);
		//tmp

		return new PermissionSimple(false);
	}

	public function getUrlParams($object, $urlParams = array(), $method = null)
	{
		$method = $method ?: (isset($urlParams['action']) ? $urlParams['action'] : 'info');
		$urlParams['action'] = isset($urlParams['action']) ? $urlParams['action'] : $method;
		$className = $this->getObjectClassName($object);
		\Onphp\Assert::isTrue(
			$this->isObjectSupported($object, $method),
			'not supported action: '.$className.'.'.$method
		);

		$objectParts = $this->classNameMapper->getUrlParts($className, $method);
		\Onphp\Assert::isNotNull($objectParts);

		return $urlParams
			+ $objectParts
			+ ['id' => ($object instanceof \Onphp\IdentifiableObject ? $this->getObjectId($object) : '')];
	}

	/**
	 * Создает url к контроллеру объекта, отвечающему за показ, редактирование
	 * Параметр $method если указан, то он определяет префикс действия пользователя.
	 *   Если не указан, то префикс действия берется из $urlParams['action']
	 *   Если и его нет, то тогда префикс = 'info'
	 * @param mixed $object
	 * @param array $urlParams
	 * @param string $method
	 * @return string
	 */
	public function getUrl($object, $urlParams = array(), $method = null)
	{
		return $this->baseUrl . http_build_query($this->getUrlParams($object, $urlParams, $method));
	}

	/**
	 * Возвращает url к логам редактирования объекта через toolkit
	 * @param mixed $object
	 * @param array $urlParams
	 * @return type
	 */
	public function getUrlLog($object, $urlParams = array())
	{
		\Onphp\Assert::isTrue(is_object($object), '$object is not an object');
		\Onphp\Assert::isInstance($object, '\Onphp\IdentifiableObject', '$object is not identifiable object');
		\Onphp\Assert::isTrue(
			$this->isObjectSupported($this->logClassName, 'info'),
			'not supported logs for object'.$this->getObjectClassName($object)
		);

		$urlParams += array(
			'object' => "{$this->logClassName}List",
			'action' => 'list',
			'objectName' => array(
				'eq' => $this->getObjectClassName($object),
			),
			'objectId' => array(
				'eq' => $this->getObjectId($object),
			),
			'id' => array(
				'sort' => 'desc',
				'order' => '1',
			)
		);

		return $this->baseUrl . http_build_query($urlParams);
	}

	/**
	 * Возвращает имя диалогового окна, в котором должна происходить работа с объектом
	 * @param mixed $object
	 * @param string $method
	 * @return string
	 */
	public function getDialogName($object, $method = null)
	{
		if (!$this->isObjectSupported($object, $method ?: 'info'))
			throw new PermissionException('not supported object');

		$objectClassName = $this->getObjectClassName($object);
		return $objectClassName.(is_object($object) ? $this->getObjectId($object) : '');
	}

	protected function getObjectId(\Onphp\IdentifiableObject $object)
	{
		return $object->getId();
	}

	/**
	 * @return \Onphp\Utils\PermissionManager
	 */
	private function getPermissionManager() {
		return $this->getServiceLocator()->get('permissionManager');
	}

	/**
	 * @param mixed $object string|object
	 */
	private function getObjectClassName($object) {
		return $this->getPermissionManager()->getObjectName($object);
	}
}
