<?php

namespace Awesome\Frontend\Model\Handler;

use Awesome\Cache\Model\Cache;
use Awesome\Frontend\Block\Root;
use Awesome\Frontend\Model\TemplateRenderer;
use Awesome\Frontend\Model\XmlParser\Layout as LayoutXmlParser;

class Layout extends \Awesome\Framework\Model\AbstractHandler
{
    /**
     * @var LayoutXmlParser $layoutXmlParser
     */
    private $layoutXmlParser;

    /**
     * @var string $view
     */
    private $view;

    /**
     * LayoutHandler constructor.
     */
    function __construct()
    {
        parent::__construct();
        $this->layoutXmlParser = new LayoutXmlParser();
    }

    /**
     * Render the page according to XML handle.
     * @inheritDoc
     */
    public function process($handle)
    {
        $handle = $this->parse($handle);

        if (!$pageContent = $this->cache->get(Cache::FULL_PAGE_CACHE_KEY, $handle)) {
            $structure = $this->layoutXmlParser->setView($this->view)
                ->get($handle);

            $templateRenderer = new TemplateRenderer($handle, $this->view, $structure['body']['children']);
            $html = new Root($templateRenderer, 'root', null, $structure);

            $pageContent = $html->toHtml();

            $this->cache->save(Cache::FULL_PAGE_CACHE_KEY, $handle, $pageContent);
        }

        return $pageContent ?: '';
    }

    /**
     * @inheritDoc
     */
    public function exist($handle)
    {
        $handle = $this->parse($handle);

        return in_array($handle, $this->layoutXmlParser->getHandlesForView($this->view));
    }

    /**
     * Handle should consists of three parts, missing ones will be added automatically as 'index'.
     * @inheritDoc
     * @return string
     */
    public function parse($handle)
    {
        $handle = str_replace('/', '_', $handle);
        $parts = explode('_', $handle);
        $handle = $parts[0] . '_'           //module
            . ($parts[1] ?? 'index') . '_'  //page
            . ($parts[2] ?? 'index');       //action

        return $handle;
    }

    /**
     * Set current page view.
     * @param string $view
     * @return $this
     */
    public function setView($view)
    {
        $this->view = $view;

        return $this;
    }
}
