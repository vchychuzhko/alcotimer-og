<?php
declare(strict_types=1);

namespace Awesome\Frontend\Block;

use Awesome\Framework\Model\Locale;
use Awesome\Frontend\Model\Context;

class Root extends \Awesome\Frontend\Block\Template
{
    private const DEFAULT_LANGUAGE = 'en';
    private const LANGUAGE_MAP = [
        'en_US' => 'en',
        'uk_UA' => 'uk',
        'ru_RU' => 'ru',
    ];

    /**
     * @inheritDoc
     */
    protected $template = 'Awesome_Frontend::root.phtml';

    /**
     * @var Locale $locale
     */
    private $locale;

    /**
     * @var string  $language
     */
    private $language;

    /**
     * Root constructor.
     * @param Context $context
     * @param Locale $locale
     * @param array $data
     */
    public function __construct(Context $context, Locale $locale, array $data = [])
    {
        parent::__construct($context, $data);
        $this->locale = $locale;
    }

    /**
     * Get current page language code.
     * @return string
     */
    public function getLanguage(): string
    {
        if ($this->language === null) {
            $locale = $this->locale->getLocale();

            $this->language = self::LANGUAGE_MAP[$locale] ?? self::DEFAULT_LANGUAGE;
        }

        return $this->language;
    }
}
