<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Template;

class Repricer extends \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Template
{
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('walmart/listing/product/template/repricer/main.phtml');
    }

    public function getHelpBlockHtml(): string
    {
        $text = __(
            'Here you define the Repricer Strategy along with your ' .
            '<strong>Minimum</strong> and <strong>Maximum</strong> price limits. This allows you to apply ' .
            'specific repricing rules to selected products while keeping prices within your acceptable range.<br><br>' .
            'To use repricing:<br><br>' .
            '1. Create and configure your Repricer Strategies in Walmart Seller Center.<br>' .
            '2. Select one of those Strategies in this policy.<br>' .
            '3. Set your Min and Max price limits.<br>' .
            '4. Assign the policy to the products you want to be repriced.'
        );

        return $this->createBlock('HelpBlock')
                    ->setData(['content' => $text])
                    ->toHtml();
    }
}
