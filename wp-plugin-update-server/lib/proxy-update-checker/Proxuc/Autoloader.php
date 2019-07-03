<?php

if ( !class_exists('Proxuc_Autoloader', false) ):

	class Proxuc_Autoloader {
		private $prefix = '';
		private $rootDir = '';
		private $libraryDir = '';

		public function __construct() {
			$this->rootDir = dirname(__FILE__) . '/';
			$nameParts = explode('_', __CLASS__, 2);
			$this->prefix = $nameParts[0] . '_';

			spl_autoload_register(array($this, 'autoload'));
		}

		public function autoload($className) {

			if (strpos($className, $this->prefix) === 0) {
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