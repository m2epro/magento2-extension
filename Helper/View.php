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

class View
{
    const LISTING_CREATION_MODE_FULL = 0;
    const LISTING_CREATION_MODE_LISTING_ONLY = 1;

    const MOVING_LISTING_OTHER_SELECTED_SESSION_KEY = 'moving_listing_other_selected';
    const MOVING_LISTING_PRODUCTS_SELECTED_SESSION_KEY = 'moving_listing_products_selected';

    /** @var \Magento\Backend\Model\UrlInterface */
    private $urlBuilder;
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
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Helper\Module\Log */
    private $logHelper;
    /** @var \Magento\Framework\App\RequestInterface */
    private $request;

    /**
     * @param \Ess\M2ePro\Helper\Data $dataHelper
     * @param \Ess\M2ePro\Helper\Module\Log $logHelper
     * @param \Magento\Backend\Model\UrlInterface $urlBuilder
     * @param \Ess\M2ePro\Helper\View\Ebay $ebayViewHelper
     * @param \Ess\M2ePro\Helper\View\Amazon $amazonViewHelper
     * @param \Ess\M2ePro\Helper\View\Walmart $walmartViewHelper
     * @param \Ess\M2ePro\Helper\View\Ebay\Controller $ebayControllerHelper
     * @param \Ess\M2ePro\Helper\View\Amazon\Controller $amazonControllerHelper
     * @param \Ess\M2ePro\Helper\View\Walmart\Controller $walmartControllerHelper
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Module\Log $logHelper,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Ess\M2ePro\Helper\View\Ebay $ebayViewHelper,
        \Ess\M2ePro\Helper\View\Amazon $amazonViewHelper,
        \Ess\M2ePro\Helper\View\Walmart $walmartViewHelper,
        \Ess\M2ePro\Helper\View\Ebay\Controller $ebayControllerHelper,
        \Ess\M2ePro\Helper\View\Amazon\Controller $amazonControllerHelper,
        \Ess\M2ePro\Helper\View\Walmart\Controller $walmartControllerHelper,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->ebayViewHelper = $ebayViewHelper;
        $this->amazonViewHelper = $amazonViewHelper;
        $this->walmartViewHelper = $walmartViewHelper;
        $this->ebayControllerHelper = $ebayControllerHelper;
        $this->amazonControllerHelper = $amazonControllerHelper;
        $this->walmartControllerHelper = $walmartControllerHelper;
        $this->dataHelper = $dataHelper;
        $this->logHelper = $logHelper;
        $this->request = $request;
    }

    /**
     * @param string $viewNick
     *
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
     *
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

    public function getCurrentView(): ?string
    {
        $controllerName = $this->request->getControllerName();

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

    public function isCurrentViewEbay(): bool
    {
        return $this->getCurrentView() == \Ess\M2ePro\Helper\View\Ebay::NICK;
    }

    public function isCurrentViewAmazon(): bool
    {
        return $this->getCurrentView() == \Ess\M2ePro\Helper\View\Amazon::NICK;
    }

    public function isCurrentViewWalmart(): bool
    {
        return $this->getCurrentView() == \Ess\M2ePro\Helper\View\Walmart::NICK;
    }

    public function isCurrentViewControlPanel(): bool
    {
        return $this->getCurrentView() == \Ess\M2ePro\Helper\View\ControlPanel::NICK;
    }

    public function isCurrentViewConfiguration(): bool
    {
        return $this->getCurrentView() == \Ess\M2ePro\Helper\View\Configuration::NICK;
    }

    public function getUrl($row, $controller, $action, array $params = []): string
    {
        $component = strtolower($row->getData('component_mode'));

        return $this->urlBuilder->getUrl("*/{$component}_{$controller}/{$action}", $params);
    }

    public function getModifiedLogMessage($logMessage)
    {
        return $this->dataHelper->escapeHtml(
            $this->logHelper->decodeDescription($logMessage),
            ['a'],
            ENT_NOQUOTES
        );
    }
}
