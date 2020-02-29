<?php

namespace Awesome\Framework\Validator;

class IpValidator extends \Awesome\Framework\Model\Validator\AbstractValidator
{
    /**
     * @inheritDoc
     * @param string $item
     */
    public function valid($item)
    {
        if (!$valid = filter_var($item, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
            $this->invalid[] = $item;
        }

        return $valid;
    }
}
