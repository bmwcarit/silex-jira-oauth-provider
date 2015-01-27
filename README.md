Silex JIRA OAuth Provider
=========================

The Silex JIRA OAuth Provider provides a simple mechanism to enable your
applicaton to use the [Atlassian JIRA REST API]
(https://developer.atlassian.com/display/JIRADEV/JIRA+REST+APIs) without the
need of password transmission. Instead of using basic authentication an access
token is created using JIRAs OAuth interface. This token can then be used with a
[Guzzle HTTP Client](http://guzzle.readthedocs.org/en/latest/) to retrieve,
modify and create issues in JIRA.

Prerequisites
-------------

In order for the provider to work, you need the following

* A running [Atlassian JIRA](https://www.atlassian.com/software/jira) server.
* An application link for a generic application with an incoming
authorization configured (see the [documentation]
(https://confluence.atlassian.com/display/JIRA/Linking+to+Another+Application)
for more details).

Installation
------------

You can install the silex jira oauth provider through
[Composer](https://getcomposer.org/)

	composer require bmwcarit/silex-jira-oauth-provider "dev-master"

Usage
-----

Before you can use the provider you need to configure and register it with your
silex application. The following code shows the most common configuration
options that are necessary:

	$app->register(new JiraOAuthServiceProvider(array(
		'base_url' => 'https://www.yourcorp.com/jira/',
		'private_key' => __DIR__ . '/jira.pem',
		'consumer_key' => 'yoursecretkey',
	)));

Once the provider is registered your application will have a new controller
mounted at `/jira`. To start the authentication process simple open the route
with the name `jira-connect`. Either you redirect within your code

	$app->redirect($app['url_generator']->generate('jira-connect'));

or you add link to your twig templates.

	<a href="{{ path('jira-connect') }}">Click here to authenticate with Jira</a>

Once the authentication is successful the provider will redirect to the route
with the name `home` or, if it does not exist, to `/` of your silex application.

You can alter this behavior by adding a redirect parameter containing the name
of a route or an URL, to which the provider should redirect after successful
authentication. For example:

	$app->redirect($app['url_generator']->generate('jira-connect',
											array('redirect' => 'yourroute')));

Or in your twig template:

	<a href="{{ path('jira-connect', {redirect: 'yourroute'}) }}">
		Click here to authenticate with Jira</a>

After successful authentication you can use the [Atlassian JIRA REST API]
(https://developer.atlassian.com/display/JIRADEV/JIRA+REST+APIs) with the
available [Guzzle HTTP Client](http://guzzle.readthedocs.org/en/latest/).
For example:

	$app['jira.oauth.client']->get('rest/api/2/priority');

Configuration Options
---------------------

* **base_url:**
The base URL of your Atlassian JIRA server.
(*default*: `http://localhost:8181/`)
* **oauth_base_url:**
The path to the oauth plugin. Atlassian JIRA serves the OAuth APIs here by
default.
(*default*: `plugins/servlet/oauth/`)
* **private_key:**
The path to the private key file that authenticate your application with
Atlassian JIRA.
(*default*: `''`)
* **consumer_key:**
A string containing the consumer key that is used to authenticate your
application with Atlassian JIRA.
(*default*: `''`)
* **url_prefix.request_token:**
The URL prefix to construct the URL to request a new token. This is constructed
with the `base_url` and the `oauth_base_url`. The default of this option already
matches JIRAs default.
(*default*: `request-token`)
* **url_prefix.authorization:**
The URL prefix to construct the URL to authorize a token. This is constructed
with the `base_url` and the `oauth_base_url`. The default of this option already
matches JIRAs default.
(*default*: `authorize?oauth_token=%s`)
* **url_prefix.access_token:**
The URL prefix to construct the URL to request an access token. This is
constructed with the `base_url` and the `oauth_base_url`. The default of this
option already matches JIRAs default.
(*default*: `access-token`)
* **route_name.callback:**
The name of the route which handles the callback from Atlassian JIRA. The
callback is transmitted to JIRA and once the user allows the application to
access JIRA he will be redirected to this URL.
(*default*: `jira-callback`)
* **route_name.default_redirect:**
The name of the route to redirect the user upon successful authentication. This
route is only used if you do not set the redirect parameters on the
`jira-connect` route.
(*default*: `home`)
* **automount:**
If this is set to true the provider will automatically mount the `jira-connect`
and `jira-callback` routes under `/jira`. If you set this to false make sure you
mount the controller yourself. To do this simply call
`$app->mount('/yourpath', $app['jira.controller.provider']);`
(*default*: `true`)

License
-------

The silex-jira-oauth-provider is licensed under the MIT license.

Acknowledgment
--------------

The initial work is based on the [JIRA OAuth PHP examples]
(https://bitbucket.org/atlassian_tutorial/atlassian-oauth-examples/src/d625161454d1ca97b4515c6147b093fac9a68f7e/php/LICENSE?at=default)
by Stan Lemon
