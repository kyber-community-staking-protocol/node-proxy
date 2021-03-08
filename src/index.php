<?php
require_once(__DIR__ . "/../vendor/autoload.php");
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

// Init dependencies
if (file_exists(__DIR__ . '/../.env')) {
    // Check this file exists for local development - Heroku will use heroku config:set
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/../");
    $dotenv->load();
}
$client = new GuzzleHttp\Client();

// Set required response headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
http_response_code(200);

// Check the origin (not foolproof!)
if($_ENV["ORIGIN_DOMAIN"] !== "*") {

    $allowedOrigins = explode("|", $_ENV['ORIGIN_DOMAIN']);
    $blIsAllowed = true;
    foreach($allowedOrigins as $allowed) {
        if(in_array($allowed, [parse_url($_SERVER['HTTP_REFERER'])['host'], parse_url($_SERVER['HTTP_ORIGIN'])['host']]) === false) {
           $blIsAllowed = false;
        } else {
            $blIsAllowed = true;
            break;
        }
    }

    if($blIsAllowed === false) {
        // Origin is not whitelisted, return a 403
        http_response_code(403);
        die("You cannot be here!");
    }
}

// Check the RPC request
$rawInput = file_get_contents("php://input");
$input = json_decode($rawInput, true);

// See if we are doing an RPC call (via POST)
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Mitigate error on preflight OPTIONS call
    // Return with http status 200 but no body
    http_response_code(200);
    die;
}

// Return bad request if not valid JSON
if($input === null && json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(429);
    die("Invalid input data");
}

// Check the RPC method is allowed
$allowedMethods = explode("|", $_ENV['RPC_METHODS']);
if(in_array($input['method'], $allowedMethods) === false) {
    http_response_code(400);
    die("RPC method is not allowed!");
}

// Decide which RPC endpoint to relay to
// Basic routing... just randomize it. We will improve if it becomes an issue
$apis = [];
if($_ENV['INFURA_ID'] !== "") {
    $apis[] = $_ENV['INFURA_ID'];
}
if($_ENV['ANYBLOCK_ID'] !== "") {
    $apis[] = $_ENV['ANYBLOCK_ID'];
}
if($_ENV['RIVET_ID'] !== "") {
    $apis[] = $_ENV['RIVET_ID'];
}
if($_ENV['QUIKNODE_ID'] !== "") {
    $apis[] = $_ENV['QUIKNODE_ID'];
}

// Now add the weighted routing, if configured
if($_ENV['WEIGHTED_ROUTES'] !== "") {
    $apis = []; //clear out current config of 1 each
    $routes = explode("|", $_ENV['WEIGHTED_ROUTES']);
    foreach($routes as $route) {
       preg_match("/(\w+)\{(\d+?)\}/", $route, $con);
       if(count($con) === 3) {
           // Properly configured
           switch(strtoupper($con[1])) {
               default:
                // nothing
                break;
                case 'ANYBLOCK':
                    if($_ENV['ANYBLOCK_ID'] !== "") {
                        for($i=1;$i<=$con[2];$i++) {
                            $apis[] = $_ENV['ANYBLOCK_ID'];
                        }
                    }
                break;
                case 'INFURA':
                    if($_ENV['INFURA_ID'] !== "") {
                        for($i=1;$i<=$con[2];$i++) {
                            $apis[] = $_ENV['INFURA_ID'];
                        }
                    }
                break;
                case 'RIVET':
                    if($_ENV['RIVET_ID'] !== "") {
                        for($i=1;$i<=$con[2];$i++) {
                            $apis[] = $_ENV['RIVET_ID'];
                        }
                    }
                break;
                case 'QUIKNODE':
                    if($_ENV['QUIKNODE_ID'] !== "") {
                        for($i=1;$i<=$con[2];$i++) {
                            $apis[] = $_ENV['QUIKNODE_ID'];
                        }
                    }
                break;
           }
       }
    }
}

$endpoint = array_rand($apis, 1);

try {
    $response = $client->post(
        $apis[$endpoint], 
        [
            'json' => $input
        ]
    );

    http_response_code($response->getStatusCode());
    echo ($response->getBody()->getContents());
} catch(ClientException $e) {
    http_response_code($e->getResponse()->getStatusCode());
    if ($e->hasResponse()) {
        $objResponse = $e->getResponse();
        echo return_error_as_json((string) $objResponse->getBody());
    }
}

function return_error_as_json($varResponseFromApi)
{
    $isJson = json_decode($varResponseFromApi);
    if($isJson === null && json_last_error() !== JSON_ERROR_NONE) {
        return json_encode(["error" => $varResponseFromApi]);
    }

    return $varResponseFromApi;
}