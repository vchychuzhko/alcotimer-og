<?php

namespace Awesome\Frontend\Block\Html;

class Head extends \Awesome\Frontend\Block\Template
{
    protected $template = 'Awesome_Frontend::html/head.phtml';

    /**
     * @var array $headData
     */
    protected $headData;

    /**
     * Head constructor.
     * @inheritDoc
     */
    public function __construct($renderer, $name, $template = null, $children = [])
    {
        parent::__construct($renderer, $name, $template);
        $this->headData = $children;
    }

    /**
     * Get page title.
     * @return string
     */
    public function getTitle()
    {
        return (string) $this->getHeadData('title');
    }

    /**
     * Get page meta description.
     * @return string
     */
    public function getDescription()
    {
        return (string) $this->getHeadData('description');
    }

    /**
     * Get page meta keywords.
     * @return string
     */
    public function getKeywords()
    {
        return (string) $this->getHeadData('keywords');
    }

    /**
     * Get favicon src path.
     * @return string
     */
    public function getFavicon()
    {
        return (string) $this->getHeadData('favicon');
    }

    /**
     * Get js libs, resolving their paths.
     * @return array
     */
    public function getLibs()
    {
        $libs = $this->getHeadData('lib') ?? [];

        foreach ($libs as $index => $lib) {
            $libs[$index] = $this->resolveAssetPath($lib, 'lib');
        }

        return $libs;
    }

    /**
     * Get scripts, resolving their paths.
     * @return array
     */
    public function getScripts()
    {
        $scripts = $this->getHeadData('script') ?: [];

        foreach ($scripts as $index => $script) {
            $scripts[$index] = $this->resolveAssetPath($script, 'js');
        }

        return $scripts;
    }

    /**
     * Get styles, resolving their paths.
     * @return array
     */
    public function getStyles()
    {
        $styles = $this->getHeadData('css') ?: [];

        foreach ($styles as $index => $style) {
            $styles[$index] = $this->resolveAssetPath($style, 'css');
        }

        return $styles;
    }

    /**
     * Get html head data by key.
     * Return all data if no key is specified.
     * @param string $key
     * @return mixed
     */
    public function getHeadData($key = '')
    {
        if ($key === '') {
            $data = $this->headData;
        } else {
            $data = $this->headData[$key] ?? null;
        }

        return $data;
    }

    /**
     * Resolve XML assets path.
     * @param string $path
     * @param string $type
     * @return string
     */
    private function resolveAssetPath($path, $type)
    {
        if (strpos($path, '//') === false) {
            @list($module, $file) = explode('::', $path);

            if (isset($file)) {
                $path = $module . '/' . $type . '/' . $file;
            }
            $path = $this->getStaticUrl($this->renderer->getView() . '/' . $path);
        }

        return $path;
    }
}