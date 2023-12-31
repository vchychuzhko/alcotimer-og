<?php
declare(strict_types=1);

namespace Awesome\Frontend\Block\Html;

class Body extends \Awesome\Frontend\Block\Container
{
    /**
     * Get body node name.
     * @return string
     */
    public function getHtmlTag(): string
    {
        return 'body';
    }

    /**
     * Get body class by page handles.
     * @return string
     */
    public function getHtmlClass(): string
    {
        $handles = [];

        if ($layout = $this->getLayout()) {
            $handles = $layout->getHandles();
        }

        return str_replace(['-', '_'], ['', '-'], implode(' ', $handles));
    }
}
