<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add;

class Tabs extends \Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractHorizontalTabs
{
    protected $motorsType;

    /** @var \Ess\M2ePro\Helper\Component\Ebay\Motors */
    private $componentEbayMotors;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Motors $componentEbayMotors,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Backend\Model\Auth\Session $authSession,
        array $data = []
    ) {
        parent::__construct($context, $jsonEncoder, $authSession, $data);

        $this->componentEbayMotors = $componentEbayMotors;
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        //------------------------------
        $this->setId('ebayMotorAddTabs');
        //------------------------------

        $this->setDestElementId('ebay_motor_add_tabs_container');
    }

    //------------------------------

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function _beforeToHtml()
    {
        $motorsType = $this->getMotorsType();
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
        $itemsGrid->setMotorsType($this->getMotorsType());
        $title = $this->getItemsTabTitle();

        $this->addTab('items', [
            'label'   => $this->__($title),
            'title'   => $this->__('Child Products'),
            'content' => $itemsGrid->toHtml()
        ]);
        //------------------------------

        //------------------------------
        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add\Filter\Grid $filtersGrid */
        $filtersGrid = $this->getLayout()
                    ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add\Filter\Grid::class);
        $filtersGrid->setMotorsType($this->getMotorsType());

        $this->addTab('filters', [
            'label'   => $this->__('Filters'),
            'title'   => $this->__('Filters'),
            'content' => $filtersGrid->toHtml()
        ]);
        //------------------------------

        //------------------------------
        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add\Group\Grid $groupsGrid */
        $groupsGrid = $this->getLayout()
                   ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add\Group\Grid::class);
        $groupsGrid->setMotorsType($this->getMotorsType());

        $this->addTab('groups', [
            'label'   => $this->__('Groups'),
            'title'   => $this->__('Groups'),
            'content' => $groupsGrid->toHtml()
        ]);
        //------------------------------

        $this->setActiveTab('items');

        return parent::_beforeToHtml();
    }

    protected function _toHtml()
    {
        return parent::_toHtml() . '<div id="ebay_motor_add_tabs_container"></div>';
    }

    //########################################

    public function setMotorsType($motorsType)
    {
        $this->motorsType = $motorsType;
    }

    public function getMotorsType()
    {
        if ($this->motorsType === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Motors type not set.');
        }

        return $this->motorsType;
    }

    //########################################

    public function getItemsTabTitle()
    {
        if ($this->componentEbayMotors->isTypeBasedOnEpids($this->getMotorsType())) {
            return $this->__('ePID(s)');
        }

        return $this->__('kType(s)');
    }

    //########################################
}
