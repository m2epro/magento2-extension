<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Renderer;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Renderer\Description
 */
abstract class Description extends AbstractBlock
{
    //########################################

    /**
     * We can not use \Magento\Store\Model\App\Emulation. Environment emulation is already started into Description
     * Renderer and can not be emulated again
     *
     * @return string
     */
    protected function _toHtml()
    {
        $this->setData('area', \Magento\Framework\App\Area::AREA_ADMINHTML);
        return parent::_toHtml();
    }

    //########################################
}
