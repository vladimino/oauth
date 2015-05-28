<?php

namespace OAuth2Demo\Client\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Guzzle\Http\Client;

class CoopOAuthController extends BaseController
{
    public static function addRoutes($routing)
    {
        $routing->get('/coop/oauth/start', array(new self(), 'redirectToAuthorization'))->bind('coop_authorize_start');
        $routing->get('/coop/oauth/handle', array(new self(), 'receiveAuthorizationCode'))->bind('coop_authorize_redirect');
    }

    /**
     * This page actually redirects to the COOP authorize page and begins
     * the typical, "auth code" OAuth grant type flow.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function redirectToAuthorization(Request $request)
    {
        $redirectUrl = $this->generateUrl('coop_authorize_redirect', array(), true);

        $url = 'http://coop.apps.knpuniversity.com/authorize?' . http_build_query(array(
                'response_type' => 'code',
                'client_id' => 'Top Cluck App',
                'redirect_uri' => $redirectUrl,
                'scope' => 'eggs-count profile'
            ));

        return $this->redirect($url);
    }

    /**
     * This is the URL that COOP will redirect back to after the user approves/denies access
     *
     * Here, we will get the authorization code from the request, exchange
     * it for an access token, and maybe do some other setup things.
     *
     * @param  Application $app
     * @param  Request $request
     * @return string|RedirectResponse
     */
    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        // equivalent to $_GET['code']
        $code = $request->get('code');

        if (!$code) {
            $error = $request->get('error');
            $errorDescription = $request->get('error_description');

            return $this->render('failed_authorization.twig', array(
                'response' => array(
                    'error' => $error,
                    'error_description' => $errorDescription
                )
            ));
        }

        $redirectUrl = $this->generateUrl('coop_authorize_redirect', array(), true);

        $http = new Client('http://coop.apps.knpuniversity.com', array(
            'request.options' => array(
                'exceptions' => false,
            )
        ));

        $request = $http->post('/token', null, array(
            'client_id' => 'Top Cluck App',
            'client_secret' => 'b60af9f69ceb19ebacfb7bc324a8a5dc',
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUrl
        ));

        // make a request to the token url
        $response = $request->send();
        $responseBody = $response->getBody(true);
        $responseArr = json_decode($responseBody, true);

        // if there is no access_token, we have a problem!!!
        if (!isset($responseArr['access_token'])) {
            return $this->render('failed_token_request.twig', array(
                'response' => $responseArr ? $responseArr : $response
            ));
        }

        $accessToken = $responseArr['access_token'];
        $expiresIn = $responseArr['expires_in'];
        $expiresAt = new \DateTime('+' . $expiresIn . ' seconds');

        $request = $http->get('/api/me');
        $request->addHeader('Authorization', 'Bearer ' . $accessToken);
        $response = $request->send();
        $meData = json_decode($response->getBody(), true);

        $user = $this->getLoggedInUser();
        $user->coopAccessToken = $accessToken;
        $user->coopUserId = $meData['id'];
        $user->coopAccessExpiresAt = $expiresAt;
        $this->saveUser($user);

        return $this->redirect($this->generateUrl('home'));
    }
}
