<?php

namespace Ess\M2ePro\Block\Adminhtml\Renderer\Description;

class Image extends \Ess\M2ePro\Block\Adminhtml\Renderer\Description
{
    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('rendererDescriptionImage');
        // ---------------------------------------

        $this->setTemplate('renderer/description/image.phtml');
    }
}