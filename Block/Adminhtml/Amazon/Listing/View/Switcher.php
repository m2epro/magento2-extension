<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\View;

class Switcher extends \Ess\M2ePro\Block\Adminhtml\Listing\View\Switcher
{
    public const VIEW_MODE_AMAZON          = 'amazon';
    public const VIEW_MODE_MAGENTO         = 'magento';
    public const VIEW_MODE_SELLERCENTRAL   = 'sellercentral';
    public const VIEW_MODE_SETTINGS        = 'settings';

    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;

    /** @var \Ess\M2ePro\Helper\Component\Amazon */
    private $componentAmazonHelper;

    /**
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Ess\M2ePro\Helper\Module\Support $supportHelper
     * @param \Ess\M2ePro\Helper\Component\Amazon $componentAmazonHelper
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Data\Session $sessionDataHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Component\Amazon $componentAmazonHelper,
        array $data = []
    ) {
        parent::__construct($sessionDataHelper, $context, $data);
        $this->supportHelper = $supportHelper;
        $this->componentAmazonHelper = $componentAmazonHelper;
    }

    /**
     * @return string
     */
    public function getDefaultViewMode(): string
    {
        return self::VIEW_MODE_AMAZON;
    }

    /**
     * @return string
     */
    public function getTooltip()
    {
        return $this->__(
            <<<HTML
<p>There are several <strong>View Modes</strong> available for you:</p>
    <ul>
        <li><p><strong>Amazon</strong> - displays the Product details based on Amazon Item information.
            Using this Mode, you can filter the Product list by Amazon Item parameters, apply the mass Actions
            (i.e. List, Revise, Relist, Stop, etc.) to the Channel Items, manage ASIN/ISBN assigning.</p></li>
        <li><p><strong>Settings</strong> - allows you to assign/unassign Description and Shipping Policies to
            the Listing Items, duplicate the Items or move them to another M2E Pro Listing.</p></li>
        <li><p><strong>Seller Central</strong> - displays Simple and Child Products listed on Amazon in a way
            they are shown in your Seller Central. Using this Mode, you can run the mass Actions to update
            the Channel Items (i.e. List, Revise, etc.) or switch them to AFN/MFN.</p></li>
        <li><p><strong>Magento</strong> - displays the Product details based on Magento Catalog data.
            Using this Mode, you can filter the Product list by Magento Product parameters
            (i.e. Magento QTY, Stock Status, etc).</p></li>
    </ul>
<p>More detailed information you can find
<a href="%url%" target="_blank" class="external-link">here</a>.</p>
HTML
            ,
            $this->supportHelper->getDocumentationArticleUrl('x/xAMVB')
        );
    }

    /**
     * @return string
     */
    protected function getComponentMode(): string
    {
        return \Ess\M2ePro\Helper\Component\Amazon::NICK;
    }

    /**
     * @return void
     */
    protected function loadItems()
    {
        $this->items = [
            'mode' => [
                'value' => [
                    [
                        'value' => self::VIEW_MODE_AMAZON,
                        'label' => $this->componentAmazonHelper->getTitle()
                    ],
                    [
                        'value' => self::VIEW_MODE_SETTINGS,
                        'label' => $this->__('Settings')
                    ],
                    [
                        'value' => self::VIEW_MODE_SELLERCENTRAL,
                        'label' => $this->__('Seller Сentral')
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
