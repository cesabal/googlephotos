<?php

/**
 * La clase maneja las configuraciones
 */
class Config{

	private static $instance;

	public $jsonConf;

	private function __construct( $config )
	{
		foreach( $config as $keyConf=>$value )
		{
			$this->{$keyConf} = $value;
		}
	}

	/**
	 * Obtiene una instancia unica de configuracion
	 * @return [Config] La Configuracion
	 */
	public static function getInstance( $config )
	{
		if ( !self::$instance instanceof self ){
            self::$instance = new self( $config );
        }

        return self::$instance;
	}

	public function parseJsonFile( $jsonFile )
	{
		
		$jsonConf = json_decode( 
		
			file_get_contents( $jsonFile )
		);

		$this->jsonConf = $jsonConf->web;
	}
}

?>