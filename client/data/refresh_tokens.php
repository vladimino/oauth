<?php

$app = require __DIR__.'/../bootstrap.php';
use Guzzle\Http\Client;

// create our http client (Guzzle)
$http = new Client('http://coop.apps.knpuniversity.com', array(
    'request.options' => array(
        'exceptions' => false,
    )
));

// refresh all tokens expiring today or earlier
/** @var \OAuth2Demo\Client\Storage\Connection $conn */
$conn = $app['connection'];

$expiringTokens = $conn->getExpiringTokens(new \DateTime('+1 month'));

foreach ($expiringTokens as $userInfo) {

    $request = $http->post('/token', null, array(
        'client_id'     => 'Top Cluck App',
        'client_secret' => 'b60af9f69ceb19ebacfb7bc324a8a5dc',
        'grant_type'    => 'refresh_token',
        'refresh_token' => $userInfo['coopRefreshToken'],
    ));

    // make a request to the token url
    $response = $request->send();
    $responseBody = $response->getBody(true);
    $responseArr = json_decode($responseBody, true);
    //var_dump($responseArr);die;

    $accessToken = $responseArr['access_token'];
    $expiresIn = $responseArr['expires_in'];
    $expiresAt = new \DateTime('+' . $expiresIn . ' seconds');
    $refreshToken = $responseArr['refresh_token'];

    $conn->saveNewTokens(
        $userInfo['email'],
        $accessToken,
        $expiresAt,
        $refreshToken
    );

    echo sprintf(
        "Refreshing token for user %s: now expires %s\n\n",
        $userInfo['email'],
        $expiresAt->format('Y-m-d H:i:s')
    );

}