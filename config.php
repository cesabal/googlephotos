<?php

/**
 * Archivo de parametros de configuración
 */


$config = array();

/**
 * Almacena el client id proporcionado por google
 * @var String ClientID
 */
$config['googleJsonFile'] = "/work/googlephotos/tmp/client_secret_726596408115-o1hfvlhuu6u23rp8rhofidji6makgbdp.apps.googleusercontent.com.json";
$config['urlRedirect'] = "http://localhost:8080/googlephotos/public/";
$config['scopes'] = array(
	"https://www.googleapis.com/auth/photoslibrary.readonly"
);

