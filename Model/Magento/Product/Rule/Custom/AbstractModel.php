<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Product\Rule\Custom;

abstract class AbstractModel extends \Ess\M2ePro\Model\AbstractModel
{
    protected $stockItemFactory;

    //########################################

    public function __construct(
        \Magento\CatalogInventory\Model\Stock\ItemFactory $stockItemFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->stockItemFactory = $stockItemFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    abstract public function getAttributeCode();

    abstract public function getLabel();

    abstract public function getValueByProductInstance(\Magento\Catalog\Model\Product $product);

    //########################################

    /**
     * @return string
     */
    public function getInputType()
    {
        return 'string';
    }

    /**
     * @return string
     */
    public function getValueElementType()
    {
        return 'text';
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return array();
    }

    //########################################
}