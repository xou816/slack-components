<?php

namespace SlackComponents\Utils;

use SlackComponents\SlackRouter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class HttpFoundationConnector {

	public static function nowConnector(SlackRouter $router, Request $request) {
		$payload = json_decode($request->request->get('payload'), true);
		$res = $router->handleNow($payload);
		if (!is_null($res)) {
			return new JsonResponse($res->getMessage());
		} else {
			return new Response('', Response::HTTP_OK);
		}
	}

	public static function laterConnector(SlackRouter $router, Request $request) {
		$payload = json_decode($request->request->get('payload'), true);
		$router->handleAndRespond($payload);
	}

}