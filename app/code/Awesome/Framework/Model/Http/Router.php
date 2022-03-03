<?php
declare(strict_types=1);

namespace Awesome\Framework\Model\Http;

use Awesome\Cache\Model\Cache;
use Awesome\Framework\Model\AppState;
use Awesome\Framework\Model\Config;
use Awesome\Framework\Model\Http;
use Awesome\Framework\Model\Http\PostControllerInterface;
use Awesome\Framework\Model\Http\Request;
use Awesome\Framework\Model\Action\HttpDefaultAction;
use Awesome\Framework\Model\Action\MaintenanceAction;
use Awesome\Framework\Model\Action\UnauthorizedAction;
use Awesome\Framework\Model\ActionInterface;
use Awesome\Framework\Model\XmlParser\RoutesXmlParser;

class Router
{
    private const ROUTES_CACHE_TAG_PREFIX = 'routes_';

    private const FALLBACK_ROUTE = '*';

    public const ADMINHTML_CONTROLLER_FOLDER = 'Adminhtml';

    private const BACKEND_FRONTNAME_CONFIG = 'backend/front_name';
    private const HOMEPAGE_HANDLE_CONFIG = 'web/homepage';

    private ActionFactory $actionFactory;

    private AppState $appState;

    private Cache $cache;

    private Config $config;

    private RoutesXmlParser $routesXmlParser;

    private string $adminhtmlFrontname;

    private array $routes = [];

    /**
     * Router constructor.
     * @param ActionFactory $actionFactory
     * @param AppState $appState
     * @param Cache $cache
     * @param Config $config
     * @param RoutesXmlParser $routesXmlParser
     */
    public function __construct(
        ActionFactory $actionFactory,
        AppState $appState,
        Cache $cache,
        Config $config,
        RoutesXmlParser $routesXmlParser
    ) {
        $this->actionFactory = $actionFactory;
        $this->appState = $appState;
        $this->cache = $cache;
        $this->config = $config;
        $this->routesXmlParser = $routesXmlParser;
    }

    /**
     * Resolve http request and return corresponding handler.
     * @param Request $request
     * @return ActionInterface
     */
    public function match(Request $request): ActionInterface
    {
        $path = $this->getPath($request);
        $view = $this->isAdminhtml($request) ? Http::BACKEND_VIEW : Http::FRONTEND_VIEW;

        $action = $this->getAction($path, $view);

        if ($action
            && is_subclass_of($action, PostControllerInterface::class) === $request->isPost()
            && $this->isAdminhtml($request) === (bool) preg_match('/\\\\' . self::ADMINHTML_CONTROLLER_FOLDER . '\\\\/', $action)
        ) {
            return $this->actionFactory->create($action);
        }

        return $this->actionFactory->create(HttpDefaultAction::class);
    }

    /**
     * Check if requested path is a registered one and return its responsible handle.
     * @param string $path
     * @param string $view
     * @return string|null
     */
    private function getAction(string $path, string $view): ?string
    {
        $routes = $this->getRoutes($view);

        if (isset($routes[$path])) {
            return end($routes[$path]);
        }

        return isset($routes[self::FALLBACK_ROUTE]) ? end($routes[self::FALLBACK_ROUTE]) : null;
    }

    /**
     * Get requested path.
     * @param Request $request
     * @return string
     */
    private function getPath(Request $request): string
    {
        if ($request->getPath() === '/') {
            return (string) $this->config->get(self::HOMEPAGE_HANDLE_CONFIG);
        }

        if ($this->appState->isAdminhtmlEnabled()) {
            return preg_replace('/^' . $this->getAdminhtmlFrontname() . '\//', '', $request->getPath());
        }

        return $request->getPath();
    }

    /**
     * Check if request is addressed to backend.
     * @param Request $request
     * @return bool
     */
    private function isAdminhtml(Request $request): bool
    {
        return $this->appState->isAdminhtmlEnabled() && preg_match('/^' . $this->getAdminhtmlFrontname() . '\//', $request->getPath());
    }

    /**
     * Get configured backend frontname.
     * @return string
     */
    private function getAdminhtmlFrontname(): string
    {
        if (!isset($this->adminhtmlFrontname)) {
            $this->adminhtmlFrontname = (string) $this->config->get(self::BACKEND_FRONTNAME_CONFIG);
        }

        return $this->adminhtmlFrontname;
    }

    /**
     * Get maintenance action.
     * @return ActionInterface
     */
    public function getMaintenanceAction(): ActionInterface
    {
        return $this->actionFactory->create(MaintenanceAction::class);
    }

    /**
     * Get unauthorized action.
     * @return ActionInterface
     */
    public function getUnauthorizedAction(): ActionInterface
    {
        return $this->actionFactory->create(UnauthorizedAction::class);
    }

    /**
     * Get registered routes for a specified view.
     * @param string $view
     * @return array
     */
    private function getRoutes(string $view): array
    {
        if (!isset($this->routes[$view])) {
            $this->routes[$view] = $this->cache->get(Cache::ETC_CACHE_KEY, self::ROUTES_CACHE_TAG_PREFIX . $view, function () use ($view) {
                return $this->routesXmlParser->getRoutesData($view);
            });
        }

        return $this->routes[$view];
    }
}
