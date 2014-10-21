<?php
// autoloader
spl_autoload_register(array(new Favicon_Autoloader(), 'autoload'));

if (!class_exists('Favicon'))
{
	trigger_error('Autoloader not registered properly', E_USER_ERROR);
}

/**
 * Autoloader class
 *
 * @package SimplePie
 * @subpackage API
 */
class Favicon_Autoloader
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'src';
	}

	/**
	 * Autoloader
	 *
	 * @param string $class The name of the class to attempt to load.
	 */
	public function autoload($class)
	{
		// Only load the class if it starts with "SimplePie"
		if (strpos($class, 'Favicon') !== 0)
		{
			return;
		}

		include $class . '.php';
	}
}