<?php

namespace Awesome\Framework\Model;

use Awesome\Framework\Model\Config;
use Awesome\Framework\Model\Event\Manager as EventManager;
use Awesome\Framework\Model\Http\Request;
use Awesome\Framework\Model\Http\Response;
use Awesome\Framework\Model\Http\Router;
use Awesome\Framework\Model\Logger;
use Awesome\Framework\Model\Maintenance;

class Http
{
    public const VERSION = '0.3.1';

    public const FRONTEND_VIEW = 'frontend';
    public const BACKEND_VIEW = 'adminhtml';
    public const BASE_VIEW = 'base';

    public const SHOW_FORBIDDEN_CONFIG = 'show_forbidden';
    public const WEB_ROOT_CONFIG = 'web_root_is_pub';

    public const ROOT_ACTION_NAME = 'index_index_index';

    /**
     * @var Logger $logger
     */
    private $logger;

    /**
     * @var Maintenance $maintenance
     */
    private $maintenance;

    /**
     * @var Config $config
     */
    private $config;

    /**
     * @var EventManager $eventManager
     */
    private $eventManager;

    /**
     * @var Request $request
     */
    private $request;

    /**
     * Http app constructor.
     */
    public function __construct()
    {
        $this->logger = new Logger();
        $this->maintenance = new Maintenance();
        $this->config = new Config();
        $this->eventManager = new EventManager();
    }

    /**
     * Run the web application.
     */
    public function run()
    {
        $request = $this->getRequest();
        $this->logger->logVisitor($request);

        if (!$this->isMaintenance()) {
            $redirectStatus = $request->getRedirectStatusCode();
            $router = new Router();

            $this->eventManager->dispatch(
                'http_frontend_action',
                ['request' => $request, 'router' => $router]
            );

            if ($action = $router->getAction()) {
                $response = $action->execute($request);
            } elseif ($redirectStatus === Request::FORBIDDEN_REDIRECT_CODE && $this->showForbiddenPage()) {
                $response = new Response('', Response::FORBIDDEN_STATUS_CODE);
            } else {
                $response = new Response('', Response::NOTFOUND_STATUS_CODE);
            }
        } else {
            // @TODO: get request 'accept' header and return error page according to needed type
            $response = new Response(
                $this->maintenance->getMaintenancePage(),
                Response::SERVICE_UNAVAILABLE_STATUS_CODE,
                ['Content-Type' => 'text/html']
            );
        }
        // @TODO: add try {...} catch and 500 status returning for exceptions

        $response->proceed();
    }

    /**
     * Check if maintenance mode is active for user IP address.
     * @return bool
     */
    private function isMaintenance()
    {
        $ip = $this->getRequest()->getUserIp();

        return $this->maintenance->isMaintenance($ip);
    }

    /**
     * Check if it is allowed to show 403 Forbidden page.
     * @return bool
     */
    private function showForbiddenPage()
    {
        return (bool) $this->config->get(self::SHOW_FORBIDDEN_CONFIG);
    }

    /**
     * Parse and return http request.
     * @return Request
     */
    private function getRequest()
    {
        if (!$this->request) {
            $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] === 443
                ? Request::SCHEME_HTTPS
                : Request::SCHEME_HTTP;
            $url = $scheme . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $method = $_SERVER['REQUEST_METHOD'];
            $parameters = [];
            $cookies = [];
            $redirectStatus = $_SERVER['REDIRECT_STATUS'] ?? null;
            $fullActionName = $this->parseFullActionName($url);
            $userIp = $_SERVER['REMOTE_ADDR'];
            $view = $this->parseView($url);

            if ($_GET) {
                $parameters = array_merge($parameters, $_GET);
            }

            if ($_POST) {
                $parameters = array_merge($parameters, $_POST);
            }

            if ($_COOKIE) {
                $cookies = $_COOKIE;
            }

            $this->request = new Request($url, $method, $parameters, $cookies, $redirectStatus, [
                'full_action_name' => $fullActionName,
                'user_ip' => $userIp,
                'view' => $view
            ]);
        }

        return $this->request;
    }

    /**
     * Parse requested URL into a valid action name.
     * Name should consists of three parts, missing ones will be added as 'index'.
     * @param string $url
     * @return string
     */
    private function parseFullActionName($url)
    {
        $parts = explode('_', str_replace('/', '_', trim(parse_url($url, PHP_URL_PATH), '/')));

        return $parts[0]
            ? ($parts[0] . '_' . ($parts[1] ?? 'index') . '_' . ($parts[2] ?? 'index'))
            : self::ROOT_ACTION_NAME;
    }

    /**
     * Resolve page view by requested URL.
     * @param string $url
     * @return string
     */
    private function parseView($url)
    {
        // @TODO: update to resolve adminhtml view
        return self::FRONTEND_VIEW;
    }
}
