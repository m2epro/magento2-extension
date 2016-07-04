<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Development\Inspection;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

abstract class AbstractInspection extends AbstractBlock
{
    /** @var \Magento\Framework\Data\FormFactory */
    protected $formFactory;

    //########################################

    public function __construct(
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = [])
    {
        $this->formFactory = $formFactory;
        parent::__construct($context, $data);
    }

    //########################################

    protected function isShown()
    {
        return true;
    }

    //########################################
}