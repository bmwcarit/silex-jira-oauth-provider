<?php namespace bmwcarit\oauth;
/*
 * Copyright (C) 2015, BMW Car IT GmbH
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 */

use Pimple\Container;
use Silex\Api\BootableProviderInterface;
use Silex\Application;
use Pimple\ServiceProviderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use GuzzleHttp\HandlerStack;
use Exception;

class JiraOAuthServiceProvider implements ServiceProviderInterface, BootableProviderInterface  {
	protected $config;
	protected $app;
	protected $log;
	protected $session;
	protected $url_generator;

	public function __construct(array $config) {
		$defaults = array(
			'base_url' => 'http://localhost:8181/',
			'oauth_base_url' => 'plugins/servlet/oauth/',
			'private_key' => '',
            'private_key_passphrase' => '',
			'consumer_key' => '',
			'url_prefix.request_token' => 'request-token',
			'url_prefix.authorization' => 'authorize?oauth_token=%s',
			'url_prefix.access_token' => 'access-token',
			'route_name.callback' => 'jira-callback',
			'route_name.default_redirect' => 'home',
			'automount' => true
		);

		$this->config = array_merge($defaults, $config);
	}

	function boot(Application $app) {
		if ($this->config['automount']) {
			$app->mount('/jira', $app['jira.controller.provider']);
		}
	}

	function register(Container $app) {
		$this->app = $app;
		$this->log = $app['monolog'];
		$this->session = $app['session'];
		$this->url_generator = $app['url_generator'];

		$app['jira.request_token_url'] = $this->getAbsoluteURL('request_token');
		$app['jira.authorization_url'] = $this->getAbsoluteURL('authorization');
		$app['jira.access_token_url'] = $this->getAbsoluteURL('access_token');

		$app['jira.oauth.client'] = function() use ($app) {
			if (is_null($this->session->get('oauth'))) {
				$this->log->addError(
						'Jira OAuth client is not initialized correctly.' .
						'Please create valid credentials first.');
				throw new \Exception('Jira OAuth client is not initialized');
			}

			$app['jira.token'] = $this->session->get('oauth');
			$oauth = $this->getOAuth();

			return $this->getClient($oauth);
		};

		$app['jira.oauth.temp_credentials'] = $app->protect(
			function($redirect = null) {
				return $this->requestTempCredentials($redirect);
			});

		$app['jira.oauth.auth_credentials'] = $app->protect(
				function($redirect = null) {
			return $this->requestAuthCredentials($redirect);
		});

		$app['jira.oauth.auth_url'] = function() {
			return $this->makeAuthUrl();
		};

		$app['jira.default_redirect'] = function() {
			return $this->url_generator->
						generate($this->config['route_name.default_redirect']);
		};

		$app['jira.controller.provider'] = function() {
			return new JiraOAuthControllerProvider();
		};
	}

	protected function requestTempCredentials($redirect) {
		$url = $this->config['base_url'] .
				$this->app['jira.request_token_url'] .
				'?oauth_callback=' . $this->getCallbackURL($redirect);

		$this->log->addDebug('Requesting temporary auth credentials from ' .
								'JIRA at ' . $url);
		$this->requestCredentials($this->getOAuthWithToken(null), $url);

		return $this->app['jira.token'];
	}

	protected function requestAuthCredentials($redirect) {
		$url = $this->config['base_url'] . $this->app['jira.access_token_url'] .
				'?oauth_callback=' . $this->getCallbackURL($redirect) .
				'&oauth_verifier=' . $this->app['jira.oauth_verifier'];

		$this->log->addDebug('Requesting temporary auth credentials from ' .
								'JIRA at ' . $url);
		$oauth = $this->getOAuth();
		$this->requestCredentials($oauth, $url);

		$this->app->extend('jira.oauth.client', function ($client, $app) {
			$oauth = $this->getOAuth();
			return $this->getClient($oauth);
		});

		return $this->app['jira.token'];
	}

	protected function requestCredentials($oauth, $url) {
		$client = $this->getClient($oauth);
        $response = $client->post($url);
        $this->setToken($response);
	}

	protected function getCallbackURL($redirect = null) {
		$url = $this->getCallbackBaseURL().$this->url_generator->generate(
						$this->config['route_name.callback'],
						array('url' => $redirect), true);

		return urlencode($url);
	}

    protected function getCallbackBaseURL(){
        return (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') ? 'http://'.$_SERVER['HTTP_HOST'] : 'https://'.$_SERVER['HTTP_HOST'];
    }

	protected function setToken($response) {
		$body = (string) $response->getBody();

		$token = array();
		parse_str($body, $token);

		if (empty($token)) {
			throw new Exception('An error occurred while requesting' .
								'oauth token credentials');
		}

		$this->app['jira.token'] = $token;
	}


	protected function makeAuthUrl() {
		return $this->config['base_url'] .
				sprintf($this->app['jira.authorization_url'],
						urlencode($this->app['jira.token']['oauth_token']));
	}

	protected function getClient(Oauth1 $oauth) {
        $stack = HandlerStack::create();
        $stack->push($oauth);

        $client = new Client([
            'base_uri' => $this->config['base_url'],
            'handler' => $stack,
            'auth' => 'oauth'
        ]);
        return $client;
	}

	protected function getOAuth() {
		return $this->getOAuthWithToken($this->app['jira.token']);
	}

	protected function getOAuthWithToken($token) {
		return new Oauth1([
			'consumer_key'		=> $this->config['consumer_key'],
			'token'				=> $token ? $token['oauth_token'] : null,
			'token_secret'		=> $token ? $token['oauth_token_secret'] : null,
			'signature_method'	=> Oauth1::SIGNATURE_METHOD_RSA,
			'private_key_file'	=> $this->config['private_key'],
            'private_key_passphrase' => $this->config['private_key_passphrase']
		]);
	}

	protected function getAbsoluteURL($key) {
		return $this->config['oauth_base_url'] .
								$this->config['url_prefix.' . $key];
	}
}
