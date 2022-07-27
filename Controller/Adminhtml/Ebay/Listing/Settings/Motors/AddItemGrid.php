<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors;

class AddItemGrid extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Motors */
    private $componentEbayMotors;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Motors $componentEbayMotors,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->componentEbayMotors = $componentEbayMotors;
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function execute()
    {
        $motorsType = $this->getRequest()->getParam('motors_type');
        $identifierType = $this->componentEbayMotors->getIdentifierKey($motorsType);

        switch ($identifierType) {
            case 'epid':
                $block = \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add\Item\Epid\Grid::class;
                break;
            case 'ktype':
                $block = \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add\Item\Ktype\Grid::class;
                break;
            default:
                throw new \Ess\M2ePro\Model\Exception\Logic("Unknown motors type [{$motorsType}]");
        }

        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add\Item\Grid $itemsGrid */
        $itemsGrid = $this->getLayout()->createBlock($block);
        $itemsGrid->setMotorsType($motorsType);

        $this->setAjaxContent($itemsGrid);

        return $this->getResult();
    }

}
