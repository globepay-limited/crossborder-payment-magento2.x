<?php
/**
 * Copyright © 2016 Globeweb. All rights reserved.
 * See more information at http://www.hellomagento2.com
 */

namespace Globeweb\Globepayali\Model;

/**
 * Class Globepayali
 */
class Globepayali extends \Magento\Payment\Model\Method\AbstractMethod
{

    const ALIPAY_DIRECT_CODE = 'globepayalidirect';
    
    protected $_code = self::ALIPAY_DIRECT_CODE;
   
    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isGateway               = false;
    
    protected $_canAuthorize            = true;
    
    protected $_canCapture              = true;
    
    protected $_canCapturePartial       = false;
    
    protected $_canRefund               = false;
    
    protected $_canVoid                 = false;
    
    protected $_canUseInternal          = false;
    
    protected $_canUseCheckout          = true;
    
    protected $_canUseForMultishipping  = false;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $_assetRepo;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param \Magento\Framework\UrlInterface
     * @param \Magento\Framework\View\Asset\Repository
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->_urlBuilder = $urlBuilder;
        $this->_assetRepo = $assetRepo;
    }

    /**
     * Returns Target URL
     *
     * @return   string Target URL
     */
    public function getGatewayUrl()
    {
        return $this->gateway;
    }

    /**
     * Return back URL
     *
     * @return   string URL
     */
    public function getReturnURL()
    {
        return $this->_urlBuilder->getUrl('globepayali/direct/success');
    }

    /**
     * Return URL for Globepayali notify response
     *
     * @return   string URL
     */
    public function getNotifyURL()
    {
        return $this->_urlBuilder->getUrl('globepayali/direct/notify/');
    }

    /**
     * Return URL for handling redirect to Globepayali Gateway
     *
     * @return   string URL
     */
    public function getRedirectUrl()
    {
        return $this->_urlBuilder->getUrl('globepayali/direct/redirect/');
    }

    /**
     * @return string url
     */
    public function getGlobepayaliLogoUrl()
    {
        return $this->_assetRepo->getUrl('Globeweb_Globepayali::images/globepayali.png');
    }

    /**
     * Return MD5 key
     *
     * @return   string
     */
    public function getSecurityCode()
    {
        return $this->getConfigData('md5_key');
    }

    public function getPartnerId()
    {
        return $this->getConfigData('partner_id');
    }


    public function getOrderStatus()
    {
        return $this->getConfigData('order_status');
    }

    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
      
        $payment->setIsPaid(true);
        
        $payment->setTransactionId($payment->getTransactionId());
        
        return $this;
    }
}
