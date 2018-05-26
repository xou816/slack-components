<?php

namespace SlackComponents\Utils;
use GuzzleHttp\Psr7;
use GuzzleHttp\Client;

class ApiClient {

	private $options;
	private $client;
	private $builder;

	public function __construct(Client $client, $options) {
		$this->client = $client;
		$this->options = $options;
	}

	private static function apiList() {
		$list = ["api", "apps.permissions", "apps.permissions.resources", "apps.permissions.scopes", "auth", "bots", "channels", "chat", "conversations", "dialog", "dnd", "emoji", "files.comments", "files", "groups", "im", "migration", "mpim", "oauth", "pins", "reactions", "reminders", "rtm", "search", "stars", "team", "team.profile", "usergroups", "usergroups.users", "users", "users.profile"];
		usort($list, function($a, $b) {
			return strlen($b) - strlen($a);
		});
		return $list;
	}

	public function __call($name, $args = []) {
		$converted = strtolower(preg_replace('/(?<!^)[A-Z]/', '.$0', $name));
		$final = null;
		foreach (self::apiList() as $api) {
			if (substr($converted, 0, strlen($api)) === $api) {
				$ptCount = count(explode('.', $api));
				$final = $api.'.'.lcfirst(substr($name, strlen($api) - $ptCount + 1));
				break;
			}
		}
		if (!is_null($final)) {
			$args = array_filter(array_merge($args[0], [
				'token' => $this->options['app_token']
			]));
			$uri = Psr7\uri_for('https://slack.com/api/'.$final);
			$body = Psr7\stream_for(http_build_query($args));
			$req = new Psr7\Request('POST', $uri);
			$req = $req
				->withHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->withBody($body);
			return json_decode($this->client->send($req)->getBody(), true);
		} else {
			return null;
		}
	}
}