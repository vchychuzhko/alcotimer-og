<?php
declare(strict_types=1);

namespace Awesome\Frontend\Block;

use Awesome\Framework\Model\Config;
use Awesome\Frontend\Model\DeployedVersion;

class Menu extends \Awesome\Frontend\Block\Template
{
    private const SUPPORT_EMAIL_CONFIG = 'support_email_address';
    //@TODO: move this to future Contact module
    /**
     * @var Config $config
     */
    private $config;

    /**
     * Menu constructor.
     * @param Config $config
     * @param DeployedVersion $deployedVersion
     * @param array $data
     */
    public function __construct(
        Config $config,
        DeployedVersion $deployedVersion,
        array $data = []
    ) {
        parent::__construct($deployedVersion, $data);
        $this->config = $config;
    }

    /**
     * Get support email address.
     * @return string
     */
    public function getSupportEmailAddress(): string
    {
        return (string) $this->config->get(self::SUPPORT_EMAIL_CONFIG);
    }
}
