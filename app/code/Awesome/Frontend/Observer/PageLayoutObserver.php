<?php

namespace Awesome\Frontend\Observer;

use Awesome\Cache\Model\Cache;
use Awesome\Framework\Model\Config;
use Awesome\Framework\Model\Http;
use Awesome\Framework\Model\Http\Request;
use Awesome\Framework\Model\Http\Response;
use Awesome\Framework\Model\Http\Router;
use Awesome\Frontend\Model\Action\LayoutHandler;

class PageLayoutObserver implements \Awesome\Framework\Model\Event\ObserverInterface
{
    private const PAGE_HANDLES_CACHE_TAG_PREFIX = 'page-handles_';

    private const LAYOUT_XML_PATH_PATTERN = '/*/*/view/%s/layout/*_*_*.xml';

    /**
     * @var Cache $cache
     */
    private $cache;

    /**
     * @var Config $config
     */
    private $config;

    /**
     * PageLayoutObserver constructor.
     * @param Cache $cache
     * @param Config $config
     */
    public function __construct(
        Cache $cache,
        Config $config
    ) {
        $this->cache = $cache;
        $this->config = $config;
    }

    /**
     * Add layout renderer as a Http action.
     * @inheritDoc
     */
    public function execute($event)
    {
        /** @var Router $router */
        $router = $event->getRouter();
        /** @var Request $request */
        $request = $event->getRequest();

        $handle = $request->getFullActionName();
        $handles = [$handle];
        $view = $request->getView();
        $status = Response::SUCCESS_STATUS_CODE;

        if ($this->isHomepage($request)) {
            $handle = $this->getHomepageHandle();
            $handles[] = $handle;
        }

        if (!$this->handleExist($handle, $view, $router)) {
            if ($request->getRedirectStatusCode() === Request::FORBIDDEN_REDIRECT_CODE && $this->showForbiddenPage()) {
                $handle = LayoutHandler::FORBIDDEN_PAGE_HANDLE;
                $status = Response::FORBIDDEN_STATUS_CODE;
            } else {
                $handle = LayoutHandler::NOTFOUND_PAGE_HANDLE;
                $status = Response::NOTFOUND_STATUS_CODE;
            }
            $handles = [$handle];
        }

        $router->addAction(LayoutHandler::class, ['handle' => $handle, 'handles' => $handles, 'status' => $status]);
    }

    /**
     * Check if homepage is requested.
     * @param Request $request
     * @return bool
     */
    private function isHomepage($request)
    {
        return $request->getFullActionName() === Http::ROOT_ACTION_NAME;
    }

    /**
     * Get homepage handle.
     * @return string
     */
    private function getHomepageHandle()
    {
        return $this->config->get(LayoutHandler::HOMEPAGE_HANDLE_CONFIG);
    }

    /**
     * Check if it is allowed to show 403 Forbidden page.
     * @return bool
     */
    private function showForbiddenPage()
    {
        return (bool) $this->config->get(Http::SHOW_FORBIDDEN_CONFIG);
    }

    /**
     * Check if requested page handle is registered and exists in specified view.
     * @param string $handle
     * @param string $view
     * @param Router $router
     * @return bool
     */
    private function handleExist($handle, $view, $router)
    {
        list($route) = explode('_', $handle);

        return $router->getStandardRoute($route, $view) && in_array($handle, $this->getPageHandles($view), true);
    }

    /**
     * Get available page layout handles for specified view.
     * @param string $view
     * @return array
     */
    private function getPageHandles($view)
    {
        if (!$handles = $this->cache->get(Cache::LAYOUT_CACHE_KEY, self::PAGE_HANDLES_CACHE_TAG_PREFIX . $view)
        ) {
            $handles = [];
            $pattern = sprintf(self::LAYOUT_XML_PATH_PATTERN, $view);

            foreach (glob(APP_DIR . $pattern) as $collectedHandle) {
                $handles[] = basename($collectedHandle, '.xml');
            }

            $this->cache->save(Cache::LAYOUT_CACHE_KEY, self::PAGE_HANDLES_CACHE_TAG_PREFIX . $view, array_unique($handles));
        }

        return $handles;
    }
}
