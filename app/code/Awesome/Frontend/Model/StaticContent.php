<?php
declare(strict_types=1);

namespace Awesome\Frontend\Model;

use Awesome\Framework\Model\FileManager;
use Awesome\Framework\Model\Http;
use Awesome\Framework\Model\Logger;
use Awesome\Frontend\Helper\StaticContentHelper;
use Awesome\Frontend\Model\DeployedVersion;
use Awesome\Frontend\Model\Css\CssMinifier;
use Awesome\Frontend\Model\Js\JsMinifier;
use Awesome\Frontend\Model\RequireJs;

class StaticContent implements \Awesome\Framework\Model\SingletonInterface
{
    public const STATIC_FOLDER_PATH = '/pub/static/';
    public const LIB_FOLDER_PATH = 'lib';

    private const STATIC_PATH_PATTERN = '/*/*/view/%s/web/%s';
    private const LIB_PATH_PATTERN = '/lib/*/*.js';
    private const STATIC_FILE_PATTERN = '/(.*\/)app\/code\/(\w+)\/(\w+)\/view\/(\w+)\/web\/(.*)$/';
    private const LIB_FILE_PATTERN = '/\/lib\/\w+\/.*$/';

    private const PUB_FOLDER_TRIGGER = '{@pubDir}';

    /**
     * @var CssMinifier $cssMinifier
     */
    private $cssMinifier;

    /**
     * @var DeployedVersion $deployedVersion
     */
    private $deployedVersion;

    /**
     * @var FileManager $fileManager
     */
    private $fileManager;

    /**
     * @var FrontendState $frontendState
     */
    private $frontendState;

    /**
     * @var JsMinifier $jsMinifier
     */
    private $jsMinifier;

    /**
     * @var Logger $logger
     */
    private $logger;

    /**
     * @var RequireJs $requireJs
     */
    private $requireJs;

    /**
     * StaticContent constructor.
     * @param CssMinifier $cssMinifier
     * @param DeployedVersion $deployedVersion
     * @param FileManager $fileManager
     * @param FrontendState $frontendState
     * @param JsMinifier $jsMinifier
     * @param Logger $logger
     * @param RequireJs $requireJs
     */
    public function __construct(
        CssMinifier $cssMinifier,
        DeployedVersion $deployedVersion,
        FileManager $fileManager,
        FrontendState $frontendState,
        JsMinifier $jsMinifier,
        Logger $logger,
        RequireJs $requireJs
    ) {
        $this->cssMinifier = $cssMinifier;
        $this->deployedVersion = $deployedVersion;
        $this->fileManager = $fileManager;
        $this->frontendState = $frontendState;
        $this->jsMinifier = $jsMinifier;
        $this->logger = $logger;
        $this->requireJs = $requireJs;
    }

    /**
     * Deploy static files for a specified view.
     * Process both views if not specified.
     * @param string $view
     * @return $this
     */
    public function deploy(string $view = ''): self
    {
        $this->deployedVersion->generateVersion();

        if ($view === '') {
            foreach ([Http::FRONTEND_VIEW, Http::BACKEND_VIEW] as $httpView) {
                $this->processView($httpView);
            }
        } else {
            $this->processView($view);
        }

        return $this;
    }

    /**
     * Perform all needed steps for specified view.
     * @param string $view
     * @return $this
     */
    private function processView(string $view): self
    {
        $this->fileManager->removeDirectory(BP . self::STATIC_FOLDER_PATH . $view);
        $this->fileManager->createDirectory(BP . self::STATIC_FOLDER_PATH . $view);

        $this->generate($view);
        $this->requireJs->generate($view);
        $this->logger->info(sprintf('Static files were deployed for "%s" view', $view));

        return $this;
    }

    /**
     * Collect, parse and generate css/js files for requested view.
     * @param string $view
     * @return $this
     */
    private function generate(string $view): self
    {
        $fontPattern = sprintf(self::STATIC_PATH_PATTERN, '{' . Http::BASE_VIEW . ',' . $view . '}', 'fonts');

        foreach (glob(APP_DIR . $fontPattern, GLOB_ONLYDIR | GLOB_BRACE) as $folder) {
            $files = $this->fileManager->scanDirectory($folder, true, ['eot', 'ttf', 'otf', 'woff', 'woff2']);

            foreach ($files as $file) {
                $this->generateFontFile($file, $view);
            }
        }

        $cssPattern = sprintf(self::STATIC_PATH_PATTERN, '{' . Http::BASE_VIEW . ',' . $view . '}', 'css');
        $cssMinify = $this->frontendState->isCssMinificationEnabled();

        foreach ($this->globWithoutMinifiedFiles(APP_DIR . $cssPattern, GLOB_ONLYDIR | GLOB_BRACE) as $folder) {
            $files = $this->fileManager->scanDirectory($folder, true, 'css');

            foreach ($files as $file) {
                $this->generateCssFile($file, $view, $cssMinify);
            }
        }

        $jsPattern = sprintf(self::STATIC_PATH_PATTERN, '{' . Http::BASE_VIEW . ',' . $view . '}', 'js');
        $jsMinify = $this->frontendState->isJsMinificationEnabled();

        foreach ($this->globWithoutMinifiedFiles(APP_DIR . $jsPattern, GLOB_ONLYDIR | GLOB_BRACE) as $folder) {
            $files = $this->fileManager->scanDirectory($folder, true, 'js');

            foreach ($files as $file) {
                $this->generateJsFile($file, $view, $jsMinify);
            }
        }

        $libFiles = $this->globWithoutMinifiedFiles(BP . self::LIB_PATH_PATTERN);

        foreach ($libFiles as $libFile) {
            $this->generateLibFile($libFile, $view, $jsMinify);
        }

        return $this;
    }

    /**
     * Deploy static file for specified view.
     * @param string $path
     * @param string $view
     * @return $this
     */
    public function deployFile(string $path, string $view): self
    {
        if (!is_dir(BP . self::STATIC_FOLDER_PATH . $view)) {
            $this->fileManager->createDirectory(BP . self::STATIC_FOLDER_PATH . $view);
        }

        if ($path === RequireJs::RESULT_FILENAME) {
            $this->requireJs->generate($view);
        } else {
            $path = BP . '/' . ltrim(str_replace(BP, '', $path), '/');
            $extension = pathinfo($path, PATHINFO_EXTENSION);

            switch ($extension) {
                case 'eot':
                case 'ttf':
                case 'otf':
                case 'woff':
                case 'woff2': {
                    $this->generateFontFile($path, $view);
                    break;
                }
                case 'css': {
                    $minify = $this->frontendState->isCssMinificationEnabled();

                    $this->generateCssFile($path, $view, $minify);
                    break;
                }
                case 'js': {
                    $minify = $this->frontendState->isJsMinificationEnabled();

                    if (preg_match(self::LIB_FILE_PATTERN, $path)) {
                        $this->generateLibFile($path, $view, $minify);
                    } else {
                        $this->generateJsFile($path, $view, $minify);
                    }
                    break;
                }
            }
        }
        $this->logger->info(sprintf('Static file "%s" was deployed for "%s" view', $path, $view));

        return $this;
    }

    /**
     * Copy font file for requested view.
     * Absolute path is required.
     * @param string $path
     * @param string $view
     * @return $this
     */
    private function generateFontFile(string $path, string $view): self
    {
        $staticPath = preg_replace(self::STATIC_FILE_PATTERN, '/$2_$3/$5', $path);

        $this->fileManager->copyFile($path, BP . self::STATIC_FOLDER_PATH . $view . $staticPath);

        return $this;
    }

    /**
     * Parse and generate css file for requested view.
     * Absolute path is required.
     * @param string $path
     * @param string $view
     * @param bool $minify
     * @return $this
     */
    private function generateCssFile(string $path, string $view, bool $minify = false): self
    {
        $content = $this->fileManager->readFile($path);
        $content = $this->parsePubDirPath($content);

        $staticPath = preg_replace(self::STATIC_FILE_PATTERN, '/$2_$3/$5', $path);

        if ($minify) {
            if (StaticContentHelper::minifiedVersionExists($path)) {
                $path = StaticContentHelper::addMinificationFlag($path);

                $content = $this->fileManager->readFile($path);
                $content = $this->parsePubDirPath($content);
            } else {
                $content = $this->cssMinifier->minify($content);
            }
            $staticPath = StaticContentHelper::addMinificationFlag($staticPath);
        }

        $this->fileManager->createFile(BP . self::STATIC_FOLDER_PATH . $view . $staticPath, $content);

        return $this;
    }

    /**
     * Parse and generate js file for requested view.
     * Absolute path is required.
     * @param string $path
     * @param string $view
     * @param bool $minify
     * @return $this
     */
    private function generateJsFile(string $path, string $view, bool $minify = false): self
    {
        $content = $this->fileManager->readFile($path);

        $staticPath = preg_replace(self::STATIC_FILE_PATTERN, '/$2_$3/$5', $path);

        if ($minify) {
            if (StaticContentHelper::minifiedVersionExists($path)) {
                $path = StaticContentHelper::addMinificationFlag($path);

                $content = $this->fileManager->readFile($path);
            } else {
                $content = $this->jsMinifier->minify($content);
            }
            $staticPath = StaticContentHelper::addMinificationFlag($staticPath);
        }

        $this->fileManager->createFile(BP . self::STATIC_FOLDER_PATH . $view . $staticPath, $content);

        return $this;
    }

    /**
     * Copy library file for requested view.
     * Absolute path is required.
     * @param string $path
     * @param string $view
     * @param bool $minify
     * @return $this
     */
    private function generateLibFile(string $path, string $view, bool $minify = false): self
    {
        $staticPath = str_replace(BP, '', $path);

        if ($minify) {
            if (StaticContentHelper::minifiedVersionExists($path)) {
                $path = StaticContentHelper::addMinificationFlag($path);
            }
            $staticPath = StaticContentHelper::addMinificationFlag($staticPath);
        }

        $this->fileManager->copyFile($path, BP . self::STATIC_FOLDER_PATH . $view . $staticPath);

        return $this;
    }

    /**
     * Replace pub dir placeholder with the current pub URL path.
     * @param string $content
     * @return string
     */
    private function parsePubDirPath(string $content): string
    {
        $pubPath = $this->frontendState->isPubRoot() ? '/' : '/pub/';

        return str_replace(self::PUB_FOLDER_TRIGGER, $pubPath, $content);
    }

    /**
     * Perform glob skipping minified files.
     * @param string $pattern
     * @param int $flags
     * @return array
     */
    private function globWithoutMinifiedFiles(string $pattern, int $flags = 0): array
    {
        $files = glob($pattern, $flags) ?: [];

        return array_filter($files, static function ($file) {
            return !StaticContentHelper::isFileMinified($file);
        });
    }
}
