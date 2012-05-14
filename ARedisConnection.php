<?php

use Predis\Profiles\ServerProfile;
use Predis\Client;

/**
 * Represents a redis connection.
 *
 * @author Charles Pick
 * @package application.extensions.redis
 */
class ARedisConnection extends CApplicationComponent {
	/**
	 * The redis client
	 * @var Predis\Client
	 */
	protected $_client;

	/**
	 * The redis server name
	 * @var string
	 */
	public $hostname = "localhost";

	/**
	 * The redis server port
	 * @var integer
	 */
	public $port=6379;

	/**
	 * The database to use, defaults to 1
	 * @var integer
	 */
	public $database=1;

    /**
     * The password to use, defaults to none
     * @var string
     */
    public $password = null;

    /** We're using version 2.2 minimum. */
    const SERVER_VERSION = '2.2';

    /** @var bool indicates whether the Predis scripts have been registered. */
    private static $registeredScripts = false;

    /**
     * Calls the {@link registerScripts()} method.
     */
    public function init()
    {
        $this->registerScripts();
        parent::init();
    }

    /**
     * Registers Predis autoloader and includes the required files
     */
    public function registerScripts()
    {
        if (self::$registeredScripts) return;
        self::$registeredScripts = true;
        require dirname(__FILE__) . '/StaticPredisAutoloader.php';
        Yii::registerAutoloader(array('StaticPredisAutoloader','autoload'));
    }

	/**
	 * Sets the redis client to use with this connection
	 * @param Predis\Client $client the redis client instance
	 */
	public function setClient(Predis\Client $client)
	{
		$this->_client = $client;
	}

	/**
	 * Gets the redis client
	 * @return Predis\Client the redis client
	 */
	public function getClient()
	{
		if ($this->_client === null)
        {
            if (!self::$registeredScripts)
                $this->registerScripts();

            /** @var $serverProfile Predis\Profiles\ServerProfile */
            $serverProfile = Predis\Profiles\ServerProfile::get(self::SERVER_VERSION);

            $configSettings = array(
                'host' => $this->hostname,
                'port' => $this->port,
                'database' => $this->database,
            );

            if (!is_null($this->password))
            {
                $configSettings['password'] = $this->password;
            }
            /** @var $connection \Predis\Client */
            $this->_client = new Client($configSettings, $serverProfile);
            $this->_client->connect();
		}
		return $this->_client;
	}

	/**
	 * Returns a property value based on its name.
	 * Do not call this method. This is a PHP magic method that we override
	 * to allow using the following syntax to read a property
	 * <pre>
	 * $value=$component->propertyName;
	 * </pre>
	 * @param string $name the property name
	 * @return mixed the property value
	 * @throws CException if the property is not defined
	 * @see __set
	 */
	public function __get($name) {
		$getter='get'.$name;
		if (property_exists($this->getClient(),$name)) {
			return $this->getClient()->{$name};
		}
		elseif(method_exists($this->getClient(),$getter)) {
			return $this->$getter();
		}
		return parent::__get($name);
	}

	/**
	 * Sets value of a component property.
	 * Do not call this method. This is a PHP magic method that we override
	 * to allow using the following syntax to set a property
	 * <pre>
	 * $this->propertyName=$value;
	 * </pre>
	 * @param string $name the property name
	 * @param mixed $value the property value
	 * @return mixed
	 * @throws CException if the property is not defined or the property is read only.
	 * @see __get
	 */
	public function __set($name,$value)
	{
		$setter='set'.$name;
		if (property_exists($this->getClient(),$name)) {
			return $this->getClient()->{$name} = $value;
		}
		elseif(method_exists($this->getClient(),$setter)) {
			return $this->getClient()->{$setter}($value);
		}
		return parent::__set($name,$value);
	}

	/**
	 * Checks if a property value is null.
	 * Do not call this method. This is a PHP magic method that we override
	 * to allow using isset() to detect if a component property is set or not.
	 * @param string $name the property name
	 * @return boolean
	 */
	public function __isset($name)
	{
		$getter='get'.$name;
		if (property_exists($this->getClient(),$name)) {
			return true;
		}
		elseif (method_exists($this->getClient(),$getter)) {
			return true;
		}
		return parent::__isset($name);
	}

	/**
	 * Sets a component property to be null.
	 * Do not call this method. This is a PHP magic method that we override
	 * to allow using unset() to set a component property to be null.
	 * @param string $name the property name or the event name
	 * @throws CException if the property is read only.
	 * @return mixed
	 */
	public function __unset($name)
	{
		$setter='set'.$name;
		if (property_exists($this->getClient(),$name)) {
			$this->getClient()->{$name} = null;
		}
		elseif(method_exists($this,$setter)) {
			$this->$setter(null);
		}
		else {
			parent::__unset($name);
		}
	}
	/**
	 * Calls a method on the redis client with the given name.
	 * Do not call this method. This is a PHP magic method that we override to
	 * allow a facade in front of the redis object.
	 * @param string $name the name of the method to call
	 * @param array $parameters the parameters to pass to the method
	 * @return mixed the response from the redis client
	 */
	public function __call($name, $parameters) {
		return call_user_func_array(array($this->getClient(),$name),$parameters);
	}
}