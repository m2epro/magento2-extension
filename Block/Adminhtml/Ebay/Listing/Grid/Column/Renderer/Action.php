<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Grid\Column\Renderer;

use Ess\M2ePro\Block\Adminhtml\Magento\Renderer;
use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Grid as ListingGrid;

class Action extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\Action
{
    protected $ebayFactory;

    //########################################

    public function __construct(
        Renderer\CssRenderer $css,
        Renderer\JsPhpRenderer $jsPhp,
        Renderer\JsRenderer $js,
        Renderer\JsTranslatorRenderer $jsTranslatorRenderer,
        Renderer\JsUrlRenderer $jsUrlRenderer,
        \Magento\Backend\Block\Context $context,
        \Magento\Framework\Json\EncoderInterface
        $jsonEncoder,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        array $data = []
    ){
        parent::__construct($css, $jsPhp, $js, $jsTranslatorRenderer, $jsUrlRenderer, $context, $jsonEncoder, $data);
        $this->ebayFactory = $ebayFactory;
    }

    //########################################

    protected function _toOptionHtml($action, \Magento\Framework\DataObject $row)
    {
        $marketplace = $this->ebayFactory->getCachedObjectLoaded('Marketplace', $row->getData('marketplace_id'));

        if (!$marketplace->getChildObject()->isMultiMotorsEnabled() &&
            isset($action['action_id']) &&
            $action['action_id'] == ListingGrid::MASS_ACTION_ID_EDIT_PARTS_COMPATIBILITY)
        {
            return '';
        }

        return parent::_toOptionHtml($action, $row);
    }

    //########################################
}