<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder;

class Other extends AbstractModel
{
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay */
    private $componentEbayCategoryEbay;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay $componentEbayCategoryEbay,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);

        $this->componentEbayCategoryEbay = $componentEbayCategoryEbay;
    }

    public function getBuilderData()
    {
        $data = array_merge(
            $this->getConditionData(),
            $this->getConditionNoteData(),
            $this->getVatTaxData(),
            $this->getCharityData(),
            $this->getLotSizeData()
        );

        return $data;
    }

    //########################################

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getConditionData()
    {
        $this->searchNotFoundAttributes();
        $data = $this->getEbayListingProduct()->getDescriptionTemplateSource()->getCondition();

        if (!$this->processNotFoundAttributes('Condition')) {
            return [];
        }

        return [
            'item_condition' => $data
        ];
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getConditionNoteData()
    {
        $this->searchNotFoundAttributes();
        $data = $this->getEbayListingProduct()->getDescriptionTemplateSource()->getConditionNote();
        $this->processNotFoundAttributes('Seller Notes');

        return [
            'item_condition_note' => $data
        ];
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getVatTaxData()
    {
        $data = [
            'tax_category' => $this->getEbayListingProduct()->getSellingFormatTemplateSource()->getTaxCategory()
        ];

        if ($this->getEbayMarketplace()->isVatEnabled()) {
            $data['vat_mode']    = $this->getEbayListingProduct()->getEbaySellingFormatTemplate()->getVatMode();
            $data['vat_percent'] = $this->getEbayListingProduct()->getEbaySellingFormatTemplate()->getVatPercent();
        }

        if ($this->getEbayMarketplace()->isTaxTableEnabled()) {
            $data['use_tax_table'] = $this->getEbayListingProduct()
                ->getEbaySellingFormatTemplate()
                ->isTaxTableEnabled();
        }

        return $data;
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getCharityData()
    {
        $charity = $this->getEbayListingProduct()->getEbaySellingFormatTemplate()->getCharity();

        if (empty($charity[$this->getMarketplace()->getId()])) {
            return [];
        }

        return [
            'charity_id' => $charity[$this->getMarketplace()->getId()]['organization_id'],
            'charity_percent' => $charity[$this->getMarketplace()->getId()]['percentage']
        ];
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getLotSizeData()
    {
        $categoryFeatures = $this->componentEbayCategoryEbay->getFeatures(
            $this->getEbayListingProduct()->getCategoryTemplateSource()->getMainCategory(),
            $this->getMarketplace()->getId()
        );

        if (!isset($categoryFeatures['lsd']) || $categoryFeatures['lsd'] == 1) {
            return [];
        }

        return [
            'lot_size' => $this->getEbayListingProduct()->getSellingFormatTemplateSource()->getLotSize()
        ];
    }

    //########################################
}
