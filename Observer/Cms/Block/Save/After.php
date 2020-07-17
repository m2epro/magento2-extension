<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\Cms\Block\Save;

use Ess\M2ePro\Model\Ebay\Template\Description as Description;

/**
 * Class Ess\M2ePro\Observer\Cms\Block\Save\After
 */
class After extends \Ess\M2ePro\Observer\AbstractModel
{
    const INSTRUCTION_INITIATOR = 'magento_static_block_observer';

    protected $ebayFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->ebayFactory = $ebayFactory;

        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);
    }

    //########################################

    public function process()
    {
        /** @var \Magento\Cms\Model\Block $block */
        $block = $this->getEvent()->getData('object');
        if ($block->getOrigData('content') == $block->getData('content')) {
            return;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Template\Description\Collection $templates */
        $templates = $this->ebayFactory->getObject('Template_Description')->getCollection();
        $conditions = [
            $templates->getConnection()->quoteInto(
                'description_template LIKE ?',
                '%id="'.$block->getIdentifier().'"%'
            ),
            $templates->getConnection()->quoteInto(
                'description_template LIKE ?',
                '%id="'.$block->getId().'"%'
            ),
        ];
        $templates->getSelect()->where(implode(' OR ', $conditions));

        foreach ($templates as $template) {
            /** @var \Ess\M2ePro\Model\Template\Description $template */

            /** @var \Ess\M2ePro\Model\Ebay\Template\Description\AffectedListingsProducts $affectedListingsProducts */
            $affectedListingsProducts = $this->modelFactory
                ->getObject('Ebay_Template_Description_AffectedListingsProducts');
            $affectedListingsProducts->setModel($template);

            $listingsProductsInstructionsData = [];

            foreach ($affectedListingsProducts->getIds() as $listingProductId) {
                $listingsProductsInstructionsData[] = [
                    'listing_product_id' => $listingProductId,
                    'type'               => Description::INSTRUCTION_TYPE_MAGENTO_STATIC_BLOCK_IN_DESCRIPTION_CHANGED,
                    'initiator'          => self::INSTRUCTION_INITIATOR,
                    'priority'           => 30
                ];
            }

            $this->activeRecordFactory->getObject('Listing_Product_Instruction')->getResource()
                ->add($listingsProductsInstructionsData);
        }
    }

    //########################################
}
