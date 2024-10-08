<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Category;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer;
use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\WizardTrait;
use Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\ManagerFactory;
use Ess\M2ePro\Model\Listing;

class MagentoCategory extends AbstractContainer
{
    use WizardTrait;

    private array $categoriesData;
    private ManagerFactory $wizardManagerFactory;

    private Listing $listing;

    public function __construct(
        array $categoriesData,
        ManagerFactory $wizardManagerFactory,
        Listing $listing,
        Widget $context,
        array $data = []
    ) {
        $this->categoriesData = $categoriesData;
        $this->wizardManagerFactory = $wizardManagerFactory;
        $this->listing = $listing;

        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('listingCategoryManually');

        //@todo refactor - remove all direct request calls
        $wizardId = (int)$this->getWizardIdFromRequest();
        $wizardManager = $this->wizardManagerFactory->createById($wizardId);

        $this->_headerText = $this->__('Set Category (manually)');

        $this->prepareButtons(
            [
                'id' => 'ebay_listing_category_continue_btn',
                'class' => 'action-primary forward',
                'label' => __('Continue'),
                'onclick' => 'EbayListingProductCategorySettingsModeCategoryGridObj.completeCategoriesDataStep(1, 1)',
            ],
            $wizardManager,
        );
    }

    protected function _prepareLayout()
    {
        $gridBlock = $this
            ->getLayout()
            ->createBlock(
                \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Category\ModeMagentoCategory\Grid::class,
                '',
                [
                    'listing' => $this->listing,
                    'categoriesData' => $this->categoriesData,
                ],
            );

        $this->setChild('grid', $gridBlock);

        return parent::_prepareLayout();
    }

    protected function _toHtml()
    {
        $parentHtml = parent::_toHtml();
        $popupsHtml = $this->getPopupsHtml();

        return <<<HTML
<div id="products_progress_bar"></div>
<div id="products_container">{$parentHtml}</div>
<div style="display: none">{$popupsHtml}</div>
HTML;
    }

    //########################################

    private function getPopupsHtml()
    {
        return $this->getLayout()
                    ->createBlock(
                        \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode\WarningPopup::class
                    )
                    ->toHtml();
    }
}
