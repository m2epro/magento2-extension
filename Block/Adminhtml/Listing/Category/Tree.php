<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing\Category;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Listing\Category\Tree
 */
class Tree extends \Ess\M2ePro\Block\Adminhtml\Magento\Category\AbstractCategory
{
    protected $_template = 'listing/category/tree.phtml';

    protected $_selectedCategories = [];

    protected $_highlightedCategories = [];

    protected $_callback = null;

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('listingCategoryTree');
        // ---------------------------------------

        $this->_isAjax = $this->getRequest()->isXmlHttpRequest();
    }

    //########################################

    public function getSelectedCategories()
    {
        return $this->_selectedCategories;
    }

    public function setSelectedCategories($categories)
    {
        $this->_selectedCategories = $categories;
        return $this;
    }

    public function getHighlightedCategories()
    {
        return $this->_highlightedCategories;
    }

    public function setHighlightedCategories($categories)
    {
        $this->_highlightedCategories = $categories;
        return $this;
    }

    public function getCallback()
    {
        return $this->_callback;
    }

    public function setCallback($callback)
    {
        $this->_callback = $callback;
        return $this;
    }

    //########################################

    public function buildNodeName($node)
    {
        return $this->escapeHtml($node->getName());
    }

    public function getTreeJson($parentNodeCategory = null)
    {
        $rootArray = $this->_getNodeJson($this->getRoot($parentNodeCategory, 0));
        $json = \Zend_Json::encode(isset($rootArray['children']) ? $rootArray['children'] : []);
        return $json;
    }

    public function _getNodeJson($node, $level = 0)
    {
        if (is_array($node)) {
            $node = new \Magento\Framework\Data\Tree\Node($node, 'entity_id', new \Magento\Framework\Data\Tree);
        }

        $item = [];
        $item['text'] = $this->buildNodeName($node);
        $item['id']  = $node->getId();
        $item['allowDrop'] = false;

        if ((int)$node->getChildrenCount()>0) {
            $item['children'] = [];
        }

        $isParent = false;
        if ($node->hasChildren()) {
            $item['children'] = [];
            if (!($this->getUseAjax() && $node->getLevel() > 1 && !$isParent)) {
                foreach ($node->getChildren() as $child) {
                    $item['children'][] = $this->_getNodeJson($child, $level+1);
                }
            }
        }

        if ($isParent || $node->getLevel() < 2) {
            $item['expanded'] = true;
        }

        return $item;
    }

    //########################################
}
