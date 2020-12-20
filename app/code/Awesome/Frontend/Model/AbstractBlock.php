<?php
declare(strict_types=1);

namespace Awesome\Frontend\Model;

use Awesome\Frontend\Model\Layout;

abstract class AbstractBlock extends \Awesome\Framework\Model\DataObject implements \Awesome\Frontend\Model\BlockInterface
{
    /**
     * @var Layout $layout
     */
    protected $layout;

    /**
     * @var string $nameInLayout
     */
    protected $nameInLayout = '';

    /**
     * @var string $template
     */
    protected $template;

    /**
     * AbstractBlock constructor.
     * @param array $data
     */
    public function __construct(array $data = []) {
        parent::__construct($data, true);
    }

    /**
     * @inheritDoc
     */
    public function init(Layout $layout, string $nameInLayout = '', ?string $template = null): void
    {
        $this->layout = $layout;
        $this->nameInLayout = $nameInLayout;
        $this->template = $template ?: $this->template;
    }

    /**
     * @inheritDoc
     */
    public function toHtml(): string
    {
        $html = '';

        if ($layout = $this->getLayout()) {
            $html = $this->layout->renderElement($this);
        }

        return $html;
    }

    /**
     * @inheritDoc
     */
    public function getChildHtml(string $childName = ''): string
    {
        $html = '';

        if ($layout = $this->getLayout()) {
            $childNames = $layout->getChildNames($this->getNameInLayout());

            if ($childName) {
                if (in_array($childName, $childNames, true)) {
                    $html = $this->layout->render($childName);
                }
            } else {
                foreach ($childNames as $child) {
                    $html .= $this->layout->render($child);
                }
            }
        }

        return $html;
    }

    /**
     * @inheritDoc
     */
    public function getNameInLayout(): string
    {
        return $this->nameInLayout;
    }

    /**
     * @inheritDoc
     */
    public function getTemplate(): ?string
    {
        return $this->template;
    }

    /**
     * @inheritDoc
     */
    public function getLayout(): ?Layout
    {
        return $this->layout;
    }
}