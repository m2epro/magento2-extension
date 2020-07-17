<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ActiveRecord\Relation;

use \Ess\M2ePro\Model\ActiveRecord\Relation\Amazon\Factory as AmazonFactory;
use \Ess\M2ePro\Model\ActiveRecord\Relation\Ebay\Factory as EbayFactory;
use \Ess\M2ePro\Model\ActiveRecord\Relation\Walmart\Factory as WalmartFactory;

/**
 * Class \Ess\M2ePro\Model\ActiveRecord\Relation\ParentAbstract
 * @method \Ess\M2ePro\Model\ResourceModel\ActiveRecord\ActiveRecordAbstract getResource()
 */
abstract class ParentAbstract extends \Ess\M2ePro\Model\ActiveRecord\ActiveRecordAbstract
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Relation\Factory  */
    protected $relationFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Relation\Amazon\Factory  */
    protected $amazonRelationFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Relation\Walmart\Factory  */
    protected $walmartRelationFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Relation\Ebay\Factory  */
    protected $ebayRelationFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Relation */
    protected $relationObject;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Relation\Factory $relationFactory,
        \Ess\M2ePro\Model\ActiveRecord\Relation\Amazon\Factory $amazonRelationFactory,
        \Ess\M2ePro\Model\ActiveRecord\Relation\Ebay\Factory $ebayRelationFactory,
        \Ess\M2ePro\Model\ActiveRecord\Relation\Walmart\Factory $walmartRelationFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Serializer $serializer,
        \Ess\M2ePro\Model\ActiveRecord\LockManager $lockManager,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->relationFactory = $relationFactory;
        $this->amazonRelationFactory = $amazonRelationFactory;
        $this->ebayRelationFactory = $ebayRelationFactory;
        $this->walmartRelationFactory = $walmartRelationFactory;

        parent::__construct(
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $serializer,
            $lockManager,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\ActiveRecord\Relation\Factory
     */
    public function getRelationFactory()
    {
        return $this->relationFactory;
    }

    /**
     * @return AmazonFactory|EbayFactory|WalmartFactory
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getComponentRelationFactory()
    {
        if (null === $this->getComponentMode()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('`component_mode` is required');
        }

        if ($this->getComponentMode() === \Ess\M2ePro\Helper\Component\Amazon::NICK) {
            return $this->amazonRelationFactory;
        }

        if ($this->getComponentMode() === \Ess\M2ePro\Helper\Component\Ebay::NICK) {
            return $this->ebayRelationFactory;
        }

        if ($this->getComponentMode() === \Ess\M2ePro\Helper\Component\Walmart::NICK) {
            return $this->walmartRelationFactory;
        }

        throw new \Ess\M2ePro\Model\Exception\Logic('Unknown component nick ' . $this->getComponentMode());
    }

    /**
     * @param \Ess\M2ePro\Model\ActiveRecord\Relation $relationObject
     * @return $this
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function setRelation(\Ess\M2ePro\Model\ActiveRecord\Relation $relationObject)
    {
        if ($this !== $relationObject->getParentObject()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Wrong Relation Object.');
        }

        $this->relationObject = $relationObject;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\ActiveRecord\Relation
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function withRelation()
    {
        if (null === $this->relationObject) {
            $this->relationObject = $this->relationFactory->getByParent($this);
        }

        return $this->relationObject;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\ActiveRecord\Relation\ChildAbstract
     */
    public function getChildObject()
    {
        return $this->withRelation()->getChildObject();
    }

    //########################################

    public function getComponentMode()
    {
        return $this->getData('component_mode');
    }

    /**
     * @return bool
     */
    public function isComponentModeEbay()
    {
        return $this->getComponentMode() === \Ess\M2ePro\Helper\Component\Ebay::NICK;
    }

    /**
     * @return bool
     */
    public function isComponentModeAmazon()
    {
        return $this->getComponentMode() === \Ess\M2ePro\Helper\Component\Amazon::NICK;
    }

    /**
     * @return bool
     */
    public function isComponentModeWalmart()
    {
        return $this->getComponentMode() === \Ess\M2ePro\Helper\Component\Walmart::NICK;
    }

    //########################################
}
