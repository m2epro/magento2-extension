<?php

namespace Ess\M2ePro\Block\Adminhtml\Renderer\Description;

class Gallery extends \Ess\M2ePro\Block\Adminhtml\Renderer\Description
{
    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('rendererDescriptionGallery');
        // ---------------------------------------

        $this->setTemplate('renderer/description/gallery.phtml');
    }
}