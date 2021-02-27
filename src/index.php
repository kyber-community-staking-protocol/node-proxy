<?php
require_once(__DIR__ . "/../vendor/autoload.php");
use GuzzleHttp\Psr7\Request;

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
if(in_array($_ENV["ORIGIN_DOMAIN"], [parse_url($_SERVER['HTTP_REFERER'])['host'], parse_url($_SERVER['HTTP_ORIGIN'])['host']]) === false) {
    // Origin is not whitelisted, return a 403
    http_response_code(403);
    die("You cannot be here!");
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

$response = $client->post(
    'https://mainnet.infura.io/v3/'. $_ENV['INFURA_ID'], 
    [
        'json' => $input
    ]
);

http_response_code($response->getStatusCode());
echo ($response->getBody()->getContents());