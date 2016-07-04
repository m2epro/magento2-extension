<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Feedback;

class Template extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Account
     */
    private $accountModel = NULL;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\Ebay\Feedback\Template');
    }

    protected $ebayFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->ebayFactory = $ebayFactory;
        parent::__construct(
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    //########################################

    public function delete()
    {
        $temp = parent::delete();
        $temp && $this->accountModel = NULL;
        return $temp;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    public function getAccount()
    {
        if (is_null($this->accountModel)) {
            $this->accountModel = $this->ebayFactory->getCachedObjectLoaded(
                'Account',$this->getData('account_id')
            );
        }

        return $this->accountModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Account $instance
     */
    public function setAccount(\Ess\M2ePro\Model\Account $instance)
    {
        $this->accountModel = $instance;
    }

    //########################################

    public function getBody()
    {
        return $this->getData('body');
    }

    //########################################
}