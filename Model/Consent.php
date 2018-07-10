<?php

namespace Dotdigitalgroup\Email\Model;

class Consent extends \Magento\Framework\Model\AbstractModel
{
    const CONSENT_TEXT_LIMIT = '1000';

    /**
     * Single fields for the consent contact.
     *
     * @var array
     */
    public $singleFields = [
        'DATETIMECONSENTED',
        'URL',
        'USERAGENT',
        'IPADDRESS'
    ];

    /**
     * Bulk api import for consent contact fields.
     *
     * @var array
     */
    static public $bulkFields = [
        'CONSENTTEXT',
        'CONSENTURL',
        'CONSENTDATETIME',
        'CONSENTIP',
        'CONSENTUSERAGENT'
    ];

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $dateTime;

    /**
     * @var ResourceModel\Consent
     */
    private $consentResource;

    /**
     * @var ResourceModel\Contact\CollectionFactory
     */
    private $contactCollectionFacotry;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Config
     */
    private $configHelper;

    /**
     * Constructor.
     *
     * @return null
     */
    public function _construct()
    {
        $this->_init(\Dotdigitalgroup\Email\Model\ResourceModel\Consent::class);
    }

    /**
     * Consent constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Dotdigitalgroup\Email\Helper\Config $config
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Dotdigitalgroup\Email\Helper\Config $config,
        \Dotdigitalgroup\Email\Model\ResourceModel\Consent $consent,
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory $contactCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->dateTime = $dateTime;
        $this->configHelper = $config;
        $this->consentResource = $consent;
        $this->contactCollectionFacotry = $contactCollectionFactory;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * @param $websiteId
     * @param $email
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getConsentDataByContact($websiteId, $email)
    {
        //model not loaded try to load with contact email data
        if (! $this->getId()) {
            //load model using email and website id
            $contactModel = $this->contactCollectionFacotry->create()
                ->loadByCustomerEmail($email, $websiteId);
            if ($contactModel) {
                $this->consentResource->load($this, $contactModel->getEmailContactId(), 'email_contact_id');
            }
        }
        //not enabled
        if (! $this->configHelper->isConsentSubscriberEnabled($websiteId)) {
            return [];
        }

        $consentText = $this->configHelper->getConsentSubscriberText($websiteId);
        $customerConentText = $this->configHelper->getConsentCustomerText($websiteId);
        //customer checkout and registration if consent text not empty
        if ($this->isLinkMatchCustomerRegistrationOrCheckout($this->getConsentUrl()) && strlen($customerConentText)) {
            $consentText = $customerConentText;
        }
        $consentDatetime = $this->dateTime->date(\Zend_Date::ISO_8601, $this->getConsentDatetime());
        return [
            $consentText,
            $this->getConsentUrl(),
            $consentDatetime,
            $this->getConsentIp(),
            $this->getConsentUserAgent()
        ];
    }

    /**
     * @param $consentUrl
     * @return bool
     */
    private function isLinkMatchCustomerRegistrationOrCheckout($consentUrl)
    {
        if (strpos($consentUrl, 'checkout/') !== false || strpos($consentUrl, 'customer/account/') !== false) {
            return true;
        }

        return false;
    }
}
