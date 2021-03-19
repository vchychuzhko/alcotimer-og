<?php
declare(strict_types=1);

namespace Awesome\Frontend\Model\XmlParser;

use Awesome\Framework\Exception\XmlValidationException;
use Awesome\Framework\Helper\DataHelper;
use Awesome\Framework\Helper\XmlParsingHelper;
use Awesome\Framework\Model\Config;
use Awesome\Framework\Model\FileManager\XmlFileManager;
use Awesome\Frontend\Block\Container;
use Awesome\Frontend\Block\Html\Body;
use Awesome\Frontend\Block\Html\Head;
use Awesome\Frontend\Block\Root;

class LayoutXmlParser
{
    private const DEFAULT_HANDLE_NAME = 'default';
    private const LAYOUT_XML_PATH_PATTERN = '/*/*/view/%s/layout/%s.xml';
    private const LAYOUT_XSD_SCHEMA_PATH = '/Awesome/Frontend/Schema/page_layout.xsd';

    /**
     * @var Config $config
     */
    private $config;

    /**
     * @var XmlFileManager $xmlFileManager
     */
    private $xmlFileManager;

    /**
     * @var array $processedElements
     */
    private $processedElements = [];

    /**
     * @var array $assetsToRemove
     */
    private $assetsToRemove = [];

    /**
     * @var array $references
     */
    private $references = [];

    /**
     * @var array $referencesToRemove
     */
    private $referencesToRemove = [];

    /**
     * LayoutXmlParser constructor.
     * @param Config $config
     * @param XmlFileManager $xmlFileManager
     */
    public function __construct(Config $config, XmlFileManager $xmlFileManager)
    {
        $this->config = $config;
        $this->xmlFileManager = $xmlFileManager;
    }

    /**
     * Get layout structure for requested handle for a specified view.
     * @param string $handle
     * @param string $view
     * @param array $handles
     * @return array
     * @throws \Exception
     */
    public function getLayoutStructure(string $handle, string $view, array $handles = []): array
    {
        $handles = $handles ?: [$handle];
        $pattern = APP_DIR . sprintf(
            self::LAYOUT_XML_PATH_PATTERN, $view, '{' . self::DEFAULT_HANDLE_NAME . ',' . implode(',', $handles) . '}'
        );
        $head = [];
        $body = [];

        foreach (glob($pattern, GLOB_BRACE) as $layoutXmlFile) {
            $layoutData = $this->xmlFileManager->parseXmlFile($layoutXmlFile, APP_DIR . self::LAYOUT_XSD_SCHEMA_PATH);

            if ($headNode = XmlParsingHelper::getChildNode($layoutData, 'head')) {
                $head = array_replace_recursive($head, $this->parseHeadNode($headNode));
            }
            if ($bodyNode = XmlParsingHelper::getChildNode($layoutData, 'body')) {
                $body = array_replace_recursive($body, $this->parseBodyNode($bodyNode));
            }
        }

        $this->filterRemovedAssets($head);
        XmlParsingHelper::applySortOrder($head);
        $head = [
            'name'      => 'head',
            'class'     => Head::class,
            'disabled'  => false,
            'template'  => null,
            'children'  => [],
            'arguments' => [],
            'data'      => $head,
        ];

        $this->applyReferences($body);
        XmlParsingHelper::applySortOrder($body);
        $body = [
            'name'      => 'body',
            'class'     => Body::class,
            'disabled'  => false,
            'template'  => null,
            'children'  => $body,
            'arguments' => [],
            'data'      => [],
        ];

        return [
            'root' => [
                'name'      => 'root',
                'class'     => Root::class,
                'disabled'  => false,
                'template'  => null,
                'children'  => [
                    'head' => $head,
                    'body' => $body
                ],
                'arguments' => [],
                'data'      => [],
            ]
        ];
    }

    /**
     * Parse head part of XML layout node.
     * @param \SimpleXMLElement $headNode
     * @return array
     */
    private function parseHeadNode(\SimpleXMLElement $headNode): array
    {
        $headData = [
            'scripts'  => [],
            'styles'   => [],
            'preloads' => [],
        ];

        foreach ($headNode->children() as $child) {
            switch ($childName = $child->getName()) {
                case 'title':
                case 'description':
                case 'keywords':
                    $headData[$childName] = (string) $child;
                    break;
                    //@TODO: Move above attributes to page-related config, they should not be defined in XML
                case 'favicon':
                    $headData[$childName] = XmlParsingHelper::getNodeAttribute($child, 'src');
                    break;
                case 'script':
                    $parsedAsset = [
                        'async' => XmlParsingHelper::isAttributeBooleanTrue($child, 'async'),
                        'defer' => XmlParsingHelper::isAttributeBooleanTrue($child, 'defer'),
                    ];

                    if ($sortOrder = XmlParsingHelper::getNodeAttribute($child, 'sortOrder')) {
                        $parsedAsset['sortOrder'] = $sortOrder;
                    }
                    $headData['scripts'][XmlParsingHelper::getNodeAttribute($child, 'src')] = $parsedAsset;
                    break;
                case 'css':
                    $parsedAsset = [
                        'media' => XmlParsingHelper::getNodeAttribute($child, 'media') ?: null,
                    ];

                    if ($sortOrder = XmlParsingHelper::getNodeAttribute($child, 'sortOrder')) {
                        $parsedAsset['sortOrder'] = $sortOrder;
                    }
                    $headData['styles'][XmlParsingHelper::getNodeAttribute($child, 'src')] = $parsedAsset;
                    break;
                case 'preload':
                    $parsedAsset = [
                        'as'   => XmlParsingHelper::getNodeAttribute($child, 'as'),
                        'href' => XmlParsingHelper::getNodeAttribute($child, 'href'),
                        'type' => XmlParsingHelper::getNodeAttribute($child, 'type') ?: null,
                    ];

                    if ($sortOrder = XmlParsingHelper::getNodeAttribute($child, 'sortOrder')) {
                        $parsedAsset['sortOrder'] = $sortOrder;
                    }
                    $headData['preloads'][XmlParsingHelper::getNodeAttribute($child, 'href')] = $parsedAsset;
                    break;
                case 'remove':
                    $this->assetsToRemove[] = XmlParsingHelper::getNodeAttribute($child, 'src');
                    break;
            }
        }

        return $headData;
    }

    /**
     * Parse body part of XML layout node.
     * @param \SimpleXMLElement $bodyNode
     * @return array
     */
    private function parseBodyNode(\SimpleXMLElement $bodyNode): array
    {
        $body = [];

        foreach ($bodyNode->children() as $bodyItem) {
            if ($parsedItem = $this->parseElement($bodyItem)) {
                $body[XmlParsingHelper::getNodeAttributeName($bodyItem)] = $parsedItem;
            }
        }

        return $body;
    }

    /**
     * Parse block, container or reference items.
     * @param \SimpleXMLElement $elementNode
     * @return array
     * @throws XmlValidationException
     */
    private function parseElement(\SimpleXMLElement $elementNode): array
    {
        $parsedItemNode = [];
        $elementName = XmlParsingHelper::getNodeAttributeName($elementNode);

        switch ($elementNode->getName()) {
            case 'block':
                if (($configPath = XmlParsingHelper::getNodeAttribute($elementNode, 'ifConfig'))
                    && !(bool) $this->config->get($configPath)
                ) {
                    break;
                }

                $parsedItemNode = [
                    'name'      => $elementName,
                    'class'     => XmlParsingHelper::getNodeAttribute($elementNode, 'class'),
                    'disabled'  => XmlParsingHelper::isDisabled($elementNode),
                    'template'  => XmlParsingHelper::getNodeAttribute($elementNode, 'template') ?: null,
                    'children'  => [],
                    'arguments' => [],
                    'data'      => [],
                ];

                if ($sortOrder = XmlParsingHelper::getNodeAttribute($elementNode, 'sortOrder')) {
                    $parsedItemNode['sortOrder'] = $sortOrder;
                }

                foreach ($elementNode->children() as $child) {
                    if ($child->getName() === 'arguments') {
                        foreach ($child->children() as $argument) {
                            $parsedItemNode['arguments'][XmlParsingHelper::getNodeAttributeName($argument)] = XmlParsingHelper::getNodeContent($argument);
                        }
                    } elseif ($parsedChild = $this->parseElement($child)) {
                        $parsedItemNode['children'][XmlParsingHelper::getNodeAttributeName($child)] = $parsedChild;
                    }
                }

                if (in_array($elementName, $this->processedElements, true)) {
                    throw new XmlValidationException(__('Block "%1" is already declared', $elementName));
                }
                $this->processedElements[] = $elementName;
                break;
            case 'container':
                if (($configPath = XmlParsingHelper::getNodeAttribute($elementNode, 'ifConfig'))
                    && !(bool) $this->config->get($configPath)
                ) {
                    break;
                }

                $parsedItemNode = [
                    'name'      => $elementName,
                    'class'     => Container::class,
                    'disabled'  => XmlParsingHelper::isDisabled($elementNode),
                    'template'  => null,
                    'children'  => [],
                    'arguments' => [],
                    'data'      => [],
                ];

                if ($htmlTag = XmlParsingHelper::getNodeAttribute($elementNode, 'htmlTag')) {
                    $parsedItemNode['data'] = [
                        'html_tag'   => $htmlTag,
                        'html_class' => XmlParsingHelper::getNodeAttribute($elementNode, 'htmlClass') ?: null,
                        'html_id'    => XmlParsingHelper::getNodeAttribute($elementNode, 'htmlId') ?: null,
                    ];
                }

                if ($sortOrder = XmlParsingHelper::getNodeAttribute($elementNode, 'sortOrder')) {
                    $parsedItemNode['sortOrder'] = $sortOrder;
                }

                foreach ($elementNode->children() as $child) {
                    if ($parsedChild = $this->parseElement($child)) {
                        $parsedItemNode['children'][XmlParsingHelper::getNodeAttributeName($child)] = $parsedChild;
                    }
                }

                if (in_array($elementName, $this->processedElements, true)) {
                    throw new XmlValidationException(__('Container "%1" is already declared', $elementName));
                }
                $this->processedElements[] = $elementName;
                break;
            case 'referenceBlock':
            case 'referenceContainer':
                if (XmlParsingHelper::isAttributeBooleanTrue($elementNode, 'remove')) {
                    $this->referencesToRemove[] = $elementName;
                } else {
                    $reference = [
                        'children' => [],
                    ];

                    if ($sortOrder = XmlParsingHelper::getNodeAttribute($elementNode, 'sortOrder')) {
                        $reference['sortOrder'] = $sortOrder;
                    }

                    foreach ($elementNode->children() as $child) {
                        if ($parsedChild = $this->parseElement($child)) {
                            $reference['children'][XmlParsingHelper::getNodeAttributeName($child)] = $parsedChild;
                        }
                    }
                    $this->references[] = [
                        'name' => $elementName,
                        'data' => $reference,
                    ];
                }
                break;
        }

        return $parsedItemNode;
    }

    /**
     * Filter collected assets according to remove references.
     * @param array $headStructure
     * @return void
     */
    private function filterRemovedAssets(array &$headStructure): void
    {
        foreach ($this->assetsToRemove as $assetToRemove) {
            foreach ($headStructure as $headField => $unused) {
                if (in_array($headField, ['scripts', 'styles', 'preloads'], true)) {
                    unset($headStructure[$headField][$assetToRemove]);
                }
            }
        }
    }

    /**
     * Apply reference updates to a parsed layout.
     * @param array $bodyStructure
     * @return void
     */
    private function applyReferences(array &$bodyStructure): void
    {
        foreach ($this->references as $reference) {
            $bodyStructure = DataHelper::arrayReplaceByKeyRecursive($bodyStructure, $reference['name'], $reference['data']);
        }

        foreach ($this->referencesToRemove as $referenceToRemove) {
            $bodyStructure = DataHelper::arrayRemoveByKeyRecursive($bodyStructure, $referenceToRemove);
        }
    }
}
