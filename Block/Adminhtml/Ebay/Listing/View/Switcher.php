<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View;

class Switcher extends \Ess\M2ePro\Block\Adminhtml\Listing\View\Switcher
{
    public const VIEW_MODE_EBAY        = 'ebay';
    public const VIEW_MODE_MAGENTO     = 'magento';
    public const VIEW_MODE_SETTINGS    = 'settings';

    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;

    /** @var \Ess\M2ePro\Helper\Component\Ebay */
    private $ebayHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\Session $sessionDataHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Component\Ebay $ebayHelper,
        array $data = []
    ) {
        parent::__construct($sessionDataHelper, $context, $data);
        $this->supportHelper = $supportHelper;
        $this->ebayHelper = $ebayHelper;
    }

    public function getDefaultViewMode()
    {
        return self::VIEW_MODE_EBAY;
    }

    public function getTooltip()
    {
        return $this->__(
            <<<HTML
            <p>There are several <strong>View Modes</strong> available to you:</p>
            <ul>
            <li><p><strong>eBay</strong> - displays Product details with respect to eBay Item information.
            Using this Mode, you can easily filter down the list of Products based on eBay Item details as
            well as perform Actions to eBay Items in bulk (i.e. List, Revise, Relist, Stop, etc);</p></li>
            <li><p><strong>Settings</strong> - displays information about the Settings set for the Products
            (i.e. Selling Settings, eBay Categories, etc). Using this Mode, you can easily find Products by
             reference to the Settings they use as well as edit already defined Settings in bulk.</p></li>
            <li><p><strong>Magento</strong> - displays Products information with regard to Magento Catalog.
            Using this Mode, you can easily find Products based on Magento Product information
            (i.e. Magento QTY, Stock Status, etc);</p></li>
            </ul>
            <p>More detailed information you can find
            <a href="%url%" target="_blank" class="external-link">here</a>.</p>
HTML
            ,
            $this->supportHelper->getDocumentationArticleUrl('x/wP8UB')
        );
    }

    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\Component\Ebay::NICK;
    }

    protected function loadItems()
    {
        $this->items = [
            'mode' => [
                'value' => [
                    [
                        'value' => self::VIEW_MODE_EBAY,
                        'label' => $this->ebayHelper->getTitle()
                    ],
                    [
                        'value' => self::VIEW_MODE_SETTINGS,
                        'label' => $this->__('Settings')
                    ],
                    [
                        'value' => self::VIEW_MODE_MAGENTO,
                        'label' => $this->__('Magento')
                    ],
                ]
            ]
        ];
    }
}
