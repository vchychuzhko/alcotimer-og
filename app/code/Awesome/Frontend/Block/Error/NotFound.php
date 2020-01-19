<?php

namespace Awesome\Frontend\Block\Error;

class NotFound extends \Awesome\Frontend\Block\AbstractErrorBlock
{
    /**
     * @inheritDoc
     */
    public function getErrorTitle()
    {
        return '404 error: Not Found';
    }

    /**
     * @inheritDoc
     */
    public function getErrorDescription()
    {
        return 'Seems, page you are looking for is not present.';
    }
}