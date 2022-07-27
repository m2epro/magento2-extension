<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

class EditListingProductsPolicy extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Template
{
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Template\Switcher\DataLoader */
    private $componentEbayTemplateSwitcherDataLoader;

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $helperDataGlobal;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $helperDataGlobal,
        \Ess\M2ePro\Helper\Component\Ebay\Template\Switcher\DataLoader $componentEbayTemplateSwitcherDataLoader,
        \Ess\M2ePro\Model\Ebay\Template\Manager $templateManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($templateManager, $ebayFactory, $context);

        $this->componentEbayTemplateSwitcherDataLoader = $componentEbayTemplateSwitcherDataLoader;
        $this->helperDataGlobal = $helperDataGlobal;
    }

    public function execute()
    {
        $ids = $this->getRequestIds('products_id');

        if (empty($ids)) {
            $this->setAjaxContent('0', false);
            return $this->getResult();
        }

        // ---------------------------------------
        $collection = $this->ebayFactory->getObject('Listing\Product')
            ->getCollection()
            ->addFieldToFilter('id', ['in' => $ids]);
        // ---------------------------------------

        if ($collection->getSize() == 0) {
            $this->setAjaxContent('0', false);
            return $this->getResult();
        }

        // ---------------------------------------
        /** @var \Ess\M2ePro\Helper\Component\Ebay\Template\Switcher\DataLoader $dataLoader */
        $dataLoader = $this->componentEbayTemplateSwitcherDataLoader;
        $dataLoader->load($collection);
        // ---------------------------------------

        $this->helperDataGlobal->setValue('products_ids', $ids);

        $content = $this->getLayout()
                        ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Template\Edit::class);

        $this->setAjaxContent($content->toHtml());
        return $this->getResult();
    }
}
