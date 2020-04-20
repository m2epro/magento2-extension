<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Magento\Form\Element;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\StoreSwitcher
 */
class StoreSwitcher extends AbstractElement
{
    protected $layout;

    public function __construct(
        \Magento\Framework\View\LayoutInterface $layout,
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        array $data = []
    ) {
        $this->layout = $layout;

        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setNoWrapAsAddon(true);
    }

    public function getElementHtml()
    {
        $html = '';
        $htmlId = $this->getHtmlId();

        if (($beforeElementHtml = $this->getBeforeElementHtml())) {
            $html .= '<label class="addbefore" for="' .
                $htmlId .
                '">' .
                $beforeElementHtml .
                '</label>';
        }

        $html .= $this->layout->createBlock('Ess\M2ePro\Block\Adminhtml\StoreSwitcher')->addData([
            'id' => $this->getHtmlId(),
            'selected' => $this->getData('value'),
            'name' => $this->getName(),
            'display_default_store_mode' => $this->getData('display_default_store_mode'),
            'required_option' => $this->getData('required'),
            'has_empty_option' => $this->getData('has_empty_option'),
            'class' => $this->getData('class'),
            'has_default_option' => $this->getData('has_default_option'),
        ])->toHtml();

        if (($afterElementHtml = $this->getAfterElementHtml())) {
            $html .= '<label class="addafter" for="' .
                $htmlId .
                '">' .
                $afterElementHtml .
                '</label>';
        }

        return $html;
    }
}
