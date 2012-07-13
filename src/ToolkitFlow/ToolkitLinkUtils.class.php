<?php
	/**
	 * Утилита для генерации url/имени диалогового окна на информацию/редактирование/логи объекта
	 */
	class ToolkitLinkUtils implements IServiceLocatorSupport {
		use TServiceLocatorSupport;

		protected $logClassName = null;
		protected $baseUrl = null;
		protected $authorisatorName = 'admin';

		/**
		 * @return ToolkitLinkUtils
		 */
		public static function create()
		{
			return new self;
		}
		
		/**
		 * @param string $logClassName
		 * @return ToolkitLinkUtils 
		 */
		public function setLogClassName($logClassName)
		{
			$this->logClassName = $logClassName;
			return $this;
		}
		
		/**
		 * @param string $baseUrl
		 * @return ToolkitLinkUtils 
		 */
		public function setBaseUrl($baseUrl) {
			$this->baseUrl = $baseUrl;
			return $this;
		}
		
		/**
		 * @param string $authorisatorName
		 * @return ToolkitLinkUtils 
		 */
		public function setAuthorisatorName($authorisatorName) {
			$this->authorisatorName = $authorisatorName;
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
			if ($user = $this->serviceLocator->get($this->authorisatorName)->getUser()) {
				return $this->getPermissionManager()->hasPermission($user, $method, $object);
			}
			return false;
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
			$method = $method ?: (isset($urlParams['action']) ? $urlParams['action'] : 'info');
			$urlParams['action'] = isset($urlParams['action']) ? $urlParams['action'] : $method;
			Assert::isTrue(
				$this->isObjectSupported($object, $method),
				'not supported action: '.$this->getObjectName($object).'.'.$method
			);

			$urlParams += array(
				'area' => $this->getObjectName($object),
				'id' => ($object instanceof IdentifiableObject ? $object->getId() : ''),
			);

			return $this->baseUrl . http_build_query($urlParams);
		}

		/**
		 * Возвращает url к логам редактирования объекта через toolkit
		 * @param mixed $object
		 * @param array $urlParams
		 * @return type
		 */
		public function getUrlLog($object, $urlParams = array())
		{
			Assert::isTrue(is_object($object), '$object is not an object');
			Assert::isInstance($object, 'IdentifiableObject', '$object is not identifiable object');
			Assert::isTrue(
				$this->isObjectSupported($this->logClassName, 'info'),
				'not supported logs for object'.$this->getObjectName($object)
			);

			$urlParams += array(
				'area' => "{$this->logClassName}List",
				'action' => 'list',
				'objectName' => array(
					'eq' => $this->getObjectName($object),
				),
				'objectId' => array(
					'eq' => $object->getId(),
				),
				'id' => array(
					'sort' => 'desc',
					'order' => '1',
				)
			);

			return $this->baseUrl . http_build_query($urlParams);
		}

		/**
		 * Возвращает имя диалогового окна, в которм должна происходить работа с объектом
		 * @param mixed $object
		 * @param string $method
		 * @return string
		 */
		public function getDialogName($object, $method = null)
		{
			if (!$this->isObjectSupported($object, $method ?: 'info'))
				throw new PermissionException('not supported object');

			$objectClassName = $this->getObjectName($object);
			return $objectClassName.(is_object($object) ? $object->getId() : '');
		}
		
		/**
		 * @return PermissionManager
		 */
		private function getPermissionManager() {
			return $this->serviceLocator->get('permissionManager');
		}
		
		/**
		 * @param mixed $object string|object
		 */
		private function getObjectName($object) {
			return $this->getPermissionManager()->getObjectName($object);
		}
	}
?>