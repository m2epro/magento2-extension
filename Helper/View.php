<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper;

use Ess\M2ePro\Helper\View\Ebay\Controller as EbayControllerHelper;
use Ess\M2ePro\Helper\View\Amazon\Controller as AmazonControllerHelper;
use Ess\M2ePro\Helper\View\Walmart\Controller as WalmartControllerHelper;

class View extends \Ess\M2ePro\Helper\AbstractHelper
{
    const GENERAL_BLOCK_PATH = 'General';

    const LISTING_CREATION_MODE_FULL = 0;
    const LISTING_CREATION_MODE_LISTING_ONLY = 1;

    const MOVING_LISTING_OTHER_SELECTED_SESSION_KEY = 'moving_listing_other_selected';
    const MOVING_LISTING_PRODUCTS_SELECTED_SESSION_KEY = 'moving_listing_products_selected';

    protected $activeRecordFactory;
    protected $urlBuilder;
    protected $modelFactory;

    /** @var \Ess\M2ePro\Helper\View\Ebay */
    protected $ebayViewHelper;
    /** @var \Ess\M2ePro\Helper\View\Amazon */
    protected $amazonViewHelper;
    /** @var \Ess\M2ePro\Helper\View\Walmart */
    protected $walmartViewHelper;
    /** @var \Ess\M2ePro\Helper\View\Ebay\Controller */
    protected $ebayControllerHelper;
    /** @var \Ess\M2ePro\Helper\View\Amazon\Controller */
    protected $amazonControllerHelper;
    /** @var \Ess\M2ePro\Helper\View\Walmart\Controller */
    protected $walmartControllerHelper;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Ess\M2ePro\Model\ActiveRecord\Factory $modelFactory,
        \Ess\M2ePro\Helper\View\Ebay $ebayViewHelper,
        \Ess\M2ePro\Helper\View\Amazon $amazonViewHelper,
        \Ess\M2ePro\Helper\View\Walmart $walmartViewHelper,
        \Ess\M2ePro\Helper\View\Ebay\Controller $ebayControllerHelper,
        \Ess\M2ePro\Helper\View\Amazon\Controller $amazonControllerHelper,
        \Ess\M2ePro\Helper\View\Walmart\Controller $walmartControllerHelper,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($helperFactory, $context);
        $this->activeRecordFactory = $activeRecordFactory;
        $this->urlBuilder = $urlBuilder;
        $this->modelFactory = $modelFactory;
        $this->ebayViewHelper = $ebayViewHelper;
        $this->amazonViewHelper = $amazonViewHelper;
        $this->walmartViewHelper = $walmartViewHelper;
        $this->ebayControllerHelper = $ebayControllerHelper;
        $this->amazonControllerHelper = $amazonControllerHelper;
        $this->walmartControllerHelper = $walmartControllerHelper;
    }

    /**
     * @param string $viewNick
     * @return \Ess\M2ePro\Helper\View\Amazon|\Ess\M2ePro\Helper\View\Ebay|\Ess\M2ePro\Helper\View\Walmart
     */
    public function getViewHelper($viewNick = null)
    {
        if ($viewNick === null) {
            $viewNick = $this->getCurrentView();
        }

        switch ($viewNick) {
            case \Ess\M2ePro\Helper\View\Ebay::NICK:
                return $this->ebayViewHelper;
            case \Ess\M2ePro\Helper\View\Amazon::NICK:
                return $this->amazonViewHelper;
            case \Ess\M2ePro\Helper\View\Walmart::NICK:
                return $this->walmartViewHelper;
        }

        return $this->amazonViewHelper;
    }

    /**
     * @param string $viewNick
     * @return EbayControllerHelper|AmazonControllerHelper|WalmartControllerHelper
     */
    public function getControllerHelper($viewNick = null)
    {
        if ($viewNick === null) {
            $viewNick = $this->getCurrentView();
        }

        switch ($viewNick) {
            case \Ess\M2ePro\Helper\View\Ebay::NICK:
                return $this->ebayControllerHelper;
            case \Ess\M2ePro\Helper\View\Amazon::NICK:
                return $this->amazonControllerHelper;
            case \Ess\M2ePro\Helper\View\Walmart::NICK:
                return $this->walmartControllerHelper;
        }

        return $this->amazonControllerHelper;
    }

    public function getCurrentView()
    {
        $controllerName = $this->_getRequest()->getControllerName();

        if ($controllerName === null) {
            return null;
        }

        if (stripos($controllerName, \Ess\M2ePro\Helper\View\Ebay::NICK) !== false) {
            return \Ess\M2ePro\Helper\View\Ebay::NICK;
        }

        if (stripos($controllerName, \Ess\M2ePro\Helper\View\Amazon::NICK) !== false) {
            return \Ess\M2ePro\Helper\View\Amazon::NICK;
        }

        if (stripos($controllerName, \Ess\M2ePro\Helper\View\Walmart::NICK) !== false) {
            return \Ess\M2ePro\Helper\View\Walmart::NICK;
        }

        if (stripos($controllerName, \Ess\M2ePro\Helper\View\ControlPanel::NICK) !== false) {
            return \Ess\M2ePro\Helper\View\ControlPanel::NICK;
        }

        if (stripos($controllerName, 'system_config') !== false) {
            return \Ess\M2ePro\Helper\View\Configuration::NICK;
        }

        return null;
    }

    // ---------------------------------------

    public function isCurrentViewEbay()
    {
        return $this->getCurrentView() == \Ess\M2ePro\Helper\View\Ebay::NICK;
    }

    public function isCurrentViewAmazon()
    {
        return $this->getCurrentView() == \Ess\M2ePro\Helper\View\Amazon::NICK;
    }

    public function isCurrentViewWalmart()
    {
        return $this->getCurrentView() == \Ess\M2ePro\Helper\View\Walmart::NICK;
    }

    public function isCurrentViewControlPanel()
    {
        return $this->getCurrentView() == \Ess\M2ePro\Helper\View\ControlPanel::NICK;
    }

    public function isCurrentViewConfiguration()
    {
        return $this->getCurrentView() == \Ess\M2ePro\Helper\View\Configuration::NICK;
    }

    public function getUrl($row, $controller, $action, array $params = [])
    {
        $component = strtolower($row->getData('component_mode'));
        return $this->urlBuilder->getUrl("*/{$component}_{$controller}/{$action}", $params);
    }

    public function getModifiedLogMessage($logMessage)
    {
        return $this->getHelper('Data')->escapeHtml(
            $this->getHelper('Module\Log')->decodeDescription($logMessage),
            ['a'],
            ENT_NOQUOTES
        );
    }
}
