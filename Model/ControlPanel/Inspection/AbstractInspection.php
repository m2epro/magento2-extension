<?php

namespace Ess\M2ePro\Model\ControlPanel\Inspection;

use \Ess\M2ePro\Model\ControlPanel\Inspection\Result\Factory;
use \Ess\M2ePro\Helper\Factory as HelperFactory;
use \Ess\M2ePro\Model\Factory as ModelFactory;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Form\FormKey;
use \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory as ParentFactory;
use \Ess\M2ePro\Model\ActiveRecord\Factory as ActiveRecordFactory;

abstract class AbstractInspection
{
    /** @var array */
    protected $_params = [];

    /** @var \Ess\M2ePro\Model\ControlPanel\Inspection\Result[]|null */
    protected $_results;

    /** @var Factory  */
    protected $resultFactory;

    /** @var HelperFactory  */
    protected $helperFactory;

    /** @var ModelFactory */
    protected $modelFactory;

    /** @var UrlInterface */
    protected $urlBuilder;

    /** @var ResourceConnection */
    protected $resourceConnection;

    /** @var FormKey */
    protected $formKey;

    /** @var ParentFactory */
    protected $parentFactory;

    /** @var ActiveRecordFactory */
    protected $activeRecordFactory;

    //########################################

    public function __construct(
        Factory $resultFactory,
        HelperFactory $helperFactory,
        ModelFactory $modelFactory,
        UrlInterface $urlBuilder,
        ResourceConnection $resourceConnection,
        FormKey $formKey,
        ParentFactory $parentFactory,
        ActiveRecordFactory $activeRecordFactory,
        array $_params = []
    ) {
        $this->resultFactory       = $resultFactory;
        $this->helperFactory       = $helperFactory;
        $this->modelFactory        = $modelFactory;
        $this->urlBuilder          = $urlBuilder;
        $this->resourceConnection  = $resourceConnection;
        $this->formKey             = $formKey;
        $this->parentFactory       = $parentFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->_params             = $_params;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\ControlPanel\Inspection\Result[]
     */
    abstract protected function process();

    //########################################

    /**
     * @return string
     */
    abstract public function getTitle();

    /**
     * @return string
     */
    abstract public function getGroup();

    /**
     * @return string
     */
    abstract public function getExecutionSpeed();

    /** @var float */
    protected $_timeToExecute = 0.00;

    /**
     * @return string
     */
    public function getDescription()
    {
        return null;
    }

    //########################################

    public function execute()
    {
        $start = microtime(true);
        $this->_results = $this->process();
        $this->_timeToExecute = round(microtime(true) - $start, 2);

        if (empty($this->_results)) {
            $this->_results[] = $this->resultFactory->createSuccess(
                $this
            );
        }
    }

    //########################################

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }

    //########################################

    public function getResults()
    {
        if ($this->_results === null) {
            $this->execute();
        }

        return $this->_results;
    }

    /**
     * @return float
     */
    public function getTimeToExecute()
    {
        return $this->_timeToExecute;
    }

    public function getState()
    {
        $state = 0;
        foreach ($this->getResults() as $result) {
            $result->getState() > $state && $state = $result->getState();
        }

        return $state;
    }

    //########################################
}
