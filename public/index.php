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


// Se agregan las librerias necesarias
use Google\Auth\ApplicationDefaultCredentials;
use Google\Auth\Credentials\UserRefreshCredentials;
use Google\Photos\Library\V1\PhotosLibraryClient;
use Google\Photos\Library\V1\PhotosLibraryResourceFactory;
use Google\Auth\OAuth2;

/////////////////////////////////
// Inicia el cliente de google //
/////////////////////////////////
$client = new Google_Client();
$client->setAuthConfig( $conf->googleJsonFile );
$client->addScope("https://www.googleapis.com/auth/photoslibrary.readonly");
$client->setRedirectUri( $conf->urlRedirect );

// Inicia el buffer de salida
$buffer = "";

// verifica que no haya una peticion de login activa y que no exista una session de token
// esto indica que no se ha iniciado el proceso y se inicia con la acción login
// para que el usuario pueda garantizar el acceso a sus contenido de photos
if( isset( $_GET['login'] ) == false && isset( $_SESSION['token'] ) == false )
{
	 header("Location: index.php?login");
}

// Si se invoca index.php?logout, se borrarán las sessiones activas de googlefotos
// y se redirige al login de nuevo
if( isset( $_GET['logout'] ) )
{
	$_SESSION = array();
	session_destroy();
	header("Location: index.php?login");
}

// Si hay una petición de login, se imprime el formulario con la url de acceso del usuario
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

/**
 * La funcion setea una token nuevo valido, despues de agregarlo a session realiza una redireccion
 * @param [type] $token [description]
 */
function setToken( $token )
{
	$refreshToken = $token['access_token'];
    $_SESSION['token'] = serialize( $token );
    $_SESSION['expire'] = time() + $token['expires_in'];

	header("Location: index.php");

}

// Se obtiene el archivo  credencial generado en google
$jsonFile = json_decode( file_get_contents( $conf->googleJsonFile ) );

// Se realiza una verificación de la validez, del token
if( time() > $_SESSION['expire'] )
{
	// Se obtiene el refresh token desde la sessión para
	// preparar la informacón a pasar al refreshToken
	$token = unserialize($_SESSION['token']);
	
	// Se obtiene la información desde el archivo de credenciales de google
	$data =(Array)$jsonFile->web;
	
	$client_id = $data['client_id'];
	$client_secret = $data['client_secret'];
	$refresh_token = $token['access_token'];
	
	// Se realiza la petición de un token nuevo
	$token = $client->refreshToken( $refresh_token );
	$error = isset( $token['error'] ) ? $token['error'] : '';

	// Si no hay error, redirige con un token renovado
	if( empty($error) && $token )
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
	// Si el token no ha vencido, se obtiene de la session para
	// realizar los request de albumes
	$token = unserialize($_SESSION['token']);
	$error = isset( $token['error'] ) ? $token['error'] : '';

	// Si un error en la obtención del token se realiza un intento con autenticación
	// basados en cookie
	if( $error == "invalid_grant" )
	{
		$authUrl = $client->createAuthUrl();
		header("Location: " . $authUrl );
	}
	else
	{
		// Se obtiene el token desde la session
		$sessiontoken = unserialize( $_SESSION['token'] );

		// Se obtienen los datos desde el archivos de credenciales
		$jsonFile->web->refresh_token = $sessiontoken['access_token'];

		// Se realiza ensambla un objeto de identificación para las peticiones
		$authCredentials = new UserRefreshCredentials(
			"https://www.googleapis.com/auth/photoslibrary.readonly",
			(Array)$jsonFile->web
		);

		// Se configura la libreria cliente de googlephotos, que interactuará con el api
		$photosLibraryClient = new PhotosLibraryClient(['credentials' => $authCredentials]);

		// Se obtiene una lista de los albumes del usuario y se impirmen en pantalla
		$pagelistResponse = $photosLibraryClient->listAlbums( array( 'pageSize'=>1 ) );

		$buffer .= '<div>';
		$buffer .= '<h1>Ir al album::</h1>';

		foreach ($pagelistResponse as $element) {
			$buffer .= '<div>';
		    	$buffer .= '<a href="' . $element->getProductUrl() . '" target="_blank">' . $element->getTitle() . '</a>';
		    $buffer .= '</div>';
		}
		
		$buffer .= '</div>';

	}
}

echo $buffer;