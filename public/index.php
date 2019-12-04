<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

//===============================
session_name('googlephotos');
session_start();
//===============================

// Define las rutas de la estructura de directorios
define( "_PATH", realpath( __DIR__ . "/../" ) . "/" );
define( "_PUBLIC_PATH", _PATH . "public/" );
define( "_LIB_PATH", _PATH . "vendor/" );
define( "_APP_PATH", _PATH . "app/" );


// Autoload de las clases en vendor
require_once( _LIB_PATH.'autoload.php' );

// Obtiene el archivo de configuración
require_once( _PATH.'config.php' );

// Config, genera un objeto, con las configuraciones
// del archivo de configuración @see config.php;
require_once( _APP_PATH.'Config.php' );

// El manejador de la fotos de google

require_once( _APP_PATH.'GooglePhotos.php' );

$conf = Config::getInstance( $config );
$conf->parseJsonFile( $conf->googleJsonFile );


$GooglePhotos = new GooglePhotos();

use Google\Auth\ApplicationDefaultCredentials;
use Google\Auth\Credentials\UserRefreshCredentials;
use Google\Photos\Library\V1\PhotosLibraryClient;
use Google\Photos\Library\V1\PhotosLibraryResourceFactory;
use Google\Auth\OAuth2;


$client = new Google_Client();
$client->setAuthConfig( $conf->googleJsonFile );
$client->addScope("https://www.googleapis.com/auth/photoslibrary.readonly");
$client->setRedirectUri( $conf->urlRedirect );

$buffer = "";

if( isset( $_GET['login'] ) == false && isset( $_SESSION['token'] ) == false )
{
	 header("Location: index.php?login");
}

if( isset( $_GET['logout'] ) )
{
	$_SESSION = array();
	session_destroy();
	header("Location: index.php?login");
}

///////////////////
// Para el login //
///////////////////
if( isset( $_GET['login'] ) )
{
	$authUrl = $client->createAuthUrl();
	$buffer .= '<div class="box">';
		$buffer .= '<div class="request">';
			$buffer .= '<a class="login" href="' . $authUrl . '">Obtener token!</a>';
		$buffer .= '</div>';
	$buffer .= '</div>';
	
	echo $buffer;

	exit();
}

///////////////////////////////////////////////////////////
// Para cuando el login se realizó y venimos con un code //
///////////////////////////////////////////////////////////
if ( isset($_GET['code']) )
{

	// Obtiene el token, una vez la autenticación se realizó
	$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

	if( $token )
	{
	    setToken( $token );
	}
    
}

function setToken( $token )
{
	$refreshToken = $token['access_token'];
    $_SESSION['token'] = serialize( $token );
    $_SESSION['expire'] = time() + $token['expires_in'];
	// Return the user to the home page.
	header("Location: index.php");
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////
// Si hay una session activa significa que un access token fue generado, pero posiblemente ya venció,  //
// si esto pasa se solicita uno nuevo sin login de nuevo                                               //
/////////////////////////////////////////////////////////////////////////////////////////////////////////
// if ( isset( $_SESSION['token'] ) )
// {
// 	$client->setAccessToken( $_SESSION['token'] );
	// $_SESSION['token']

// Use the OAuth flow provided by the Google API Client Auth library
// to authenticate users. See the file /src/common/common.php in the samples for a complete
// authentication example.
$jsonFile = json_decode( file_get_contents( $conf->googleJsonFile ) );

if( time() > $_SESSION['expire'] )
{
	$token = unserialize($_SESSION['token']);
	
	echo 'El token Vencio hay que renovarlo tiempo :: ';
	echo 'El token Vencio hay que renovarlo tiempo :: ' . time() . '<pre>'; print_r($token); echo '</pre>';

	$data =(Array)$jsonFile->web;
	
	$client_id = $data['client_id'];
	$client_secret = $data['client_secret'];
	$refresh_token = $token['access_token'];
	
	// $token = $client->refreshToken( $refresh_token );

	if( $token )
	{
		//////////////////////////////////////
		// ACA HAY QUE PONER EL NUEVO TOKEN //
		//////////////////////////////////////
		setToken( $token );
	}
	// $token = GetRefreshedAccessToken( $client_id, $refresh_token, $client_secret );
}
else
{
	$token = unserialize($_SESSION['token']);
	// echo 'erer<pre>'; print_r($token); echo '</pre>';
	echo "El token no ha vencido token :: <pre>" . print_r($token, true) . ' </pre>';
	echo 'El token no ha vencido tiempo :: ' .  '<hr> Current : ' . time() . "<br>Expire :: "  . $_SESSION['expire'] . '<hr>';
}

$error = isset( $token['error'] ) ? $token['error'] : '';

if( $error == "invalid_grant" )
{
	$authUrl = $client->createAuthUrl();
	header("Location: " . $authUrl );
}
else
{

	$sessiontoken = unserialize( $_SESSION['token'] );
	$jsonFile->web->refresh_token = $sessiontoken['access_token'];

	$authCredentials = new UserRefreshCredentials(
		"https://www.googleapis.com/auth/photoslibrary.readonly",
		(Array)$jsonFile->web
	);

	// Set up the Photos Library Client that interacts with the API
	$photosLibraryClient = new PhotosLibraryClient(['credentials' => $authCredentials]);
	$pagelistResponse = $photosLibraryClient->listAlbums( array( 'pageSize'=>1 ) );

	$buffer .= '<div>';
	$buffer .= '<h1>Ir al album::</h1>';
	foreach ($pagelistResponse as $element) {
		$buffer .= '<div>';
	    	$buffer .= '<a href="' . $element->getProductUrl() . '" target="_blank">' . $element->getTitle() . '</a>';
	    $buffer .= '</div>';
	}

}

$buffer .= '</div>';
echo $buffer;