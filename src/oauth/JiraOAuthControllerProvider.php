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

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class JiraOAuthControllerProvider implements ControllerProviderInterface{
	protected $app;

	public function connect(Application $app) {
		$this->app = $app;
		$jira = $app['controllers_factory'];

		$jira->get('/connect/{redirect}',
				function(Request $request, $redirect) use($app) {

			$token = $app['jira.oauth.temp_credentials']($redirect);
			$app['session']->set('oauth', $token);

			return $app->redirect($app['jira.oauth.auth_url']);
		})
		->value('redirect', null)
		->bind('jira-connect');

		$jira->get('/callback', function($url, $verifier) use($app) {
			$tempToken = $app['session']->get('oauth');
			$app['jira.token'] = $tempToken;
			$app['jira.oauth_verifier'] = $verifier;

			$token = $app['jira.oauth.auth_credentials']($url);

			$app['session']->set('oauth', $token);

			return $app->redirect($url);
		})
		->convert('url', function($url, Request $request) {
			if (!$request->query->has('url')) {
				return $this->app['jira.default_redirect'];
			}

			$url = $request->get('url');
			try {
				return $this->app['url_generator']->generate($url);
			} catch (RouteNotFoundException $e) {
				return ('/' . $url);
			}
		})
		->convert('verifier', function($verifier, Request $request) {
			if (!$request->query->has('oauth_verifier')) {
				throw new \InvalidArgumentException(
								'There was no oauth verifier in the request');
			}

			return $request->get('oauth_verifier');
		})
		->bind('jira-callback');

		return $jira;
	}
}
