<?php

if ( ! class_exists(Proxuc_Autoloader::class, false) ):

	class Proxuc_Autoloader {
		private $prefix = '';
		private $rootDir = '';

		public function __construct() {
			$this->rootDir = dirname(__FILE__) . '/';
			$nameParts = explode('_', __CLASS__, 2);
			$this->prefix = $nameParts[0] . '_';

			spl_autoload_register(array($this, 'autoload'));
		}

		public function autoload($className) {

			if ( strpos($className, 'Anyape\\' ) === 0 ) {
				$class_parts = explode('\\', $className);
				$path = $this->rootDir . 'Generic/' . end($class_parts) . '.php';

				if (file_exists($path)) {
					/** @noinspection PhpIncludeInspection */
					include $path;
				}
			} elseif (strpos($className, $this->prefix) === 0) { //To know that the prefix is at the start of the classname  
				$path = substr($className, strlen($this->prefix));
				$path = str_replace('_', '/', $path);
				$path = $this->rootDir . $path . '.php';

				if (file_exists($path)) {
					/** @noinspection PhpIncludeInspection */
					include $path;
				}
			}
		}
	}

endif;