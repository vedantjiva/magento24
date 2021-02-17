<?php
namespace Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * Proxy class for @see \Magento\Framework\Api\SearchCriteriaBuilder
 */
class Proxy extends \Magento\Framework\Api\SearchCriteriaBuilder implements \Magento\Framework\ObjectManager\NoninterceptableInterface
{
    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager = null;

    /**
     * Proxied instance name
     *
     * @var string
     */
    protected $_instanceName = null;

    /**
     * Proxied instance
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_subject = null;

    /**
     * Instance shareability flag
     *
     * @var bool
     */
    protected $_isShared = null;

    /**
     * Proxy constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string $instanceName
     * @param bool $shared
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager, $instanceName = '\\Magento\\Framework\\Api\\SearchCriteriaBuilder', $shared = true)
    {
        $this->_objectManager = $objectManager;
        $this->_instanceName = $instanceName;
        $this->_isShared = $shared;
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return ['_subject', '_isShared', '_instanceName'];
    }

    /**
     * Retrieve ObjectManager from global scope
     */
    public function __wakeup()
    {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    }

    /**
     * Clone proxied instance
     */
    public function __clone()
    {
        $this->_subject = clone $this->_getSubject();
    }

    /**
     * Get proxied instance
     *
     * @return \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected function _getSubject()
    {
        if (!$this->_subject) {
            $this->_subject = true === $this->_isShared
                ? $this->_objectManager->get($this->_instanceName)
                : $this->_objectManager->create($this->_instanceName);
        }
        return $this->_subject;
    }

    /**
     * {@inheritdoc}
     */
    public function create()
    {
        return $this->_getSubject()->create();
    }

    /**
     * {@inheritdoc}
     */
    public function addFilters(array $filter)
    {
        return $this->_getSubject()->addFilters($filter);
    }

    /**
     * {@inheritdoc}
     */
    public function addFilter($field, $value, $conditionType = 'eq')
    {
        return $this->_getSubject()->addFilter($field, $value, $conditionType);
    }

    /**
     * {@inheritdoc}
     */
    public function setFilterGroups(array $filterGroups)
    {
        return $this->_getSubject()->setFilterGroups($filterGroups);
    }

    /**
     * {@inheritdoc}
     */
    public function addSortOrder($sortOrder)
    {
        return $this->_getSubject()->addSortOrder($sortOrder);
    }

    /**
     * {@inheritdoc}
     */
    public function setSortOrders(array $sortOrders)
    {
        return $this->_getSubject()->setSortOrders($sortOrders);
    }

    /**
     * {@inheritdoc}
     */
    public function setPageSize($pageSize)
    {
        return $this->_getSubject()->setPageSize($pageSize);
    }

    /**
     * {@inheritdoc}
     */
    public function setCurrentPage($currentPage)
    {
        return $this->_getSubject()->setCurrentPage($currentPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->_getSubject()->getData();
    }
}
