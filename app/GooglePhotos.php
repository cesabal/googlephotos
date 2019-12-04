<?php

use Google\Auth\ApplicationDefaultCredentials;
use Google\Auth\Credentials\UserRefreshCredentials;
use Google\Photos\Library\V1\PhotosLibraryClient;
use Google\Photos\Library\V1\PhotosLibraryResourceFactory;
use Google\Auth\OAuth2;

/**
 * La clase maneja la petición de Fotografias al api de google
 */
class GooglePhotos
{
	
	var $config;
	
	/**
	 * Contructor
	 */
	public function __construct( Config $config )
	{
		$this->config = $config;
	}



}