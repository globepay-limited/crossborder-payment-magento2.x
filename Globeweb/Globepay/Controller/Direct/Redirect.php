<?php
/**
 * Copyright © 2016 Globeweb. All rights reserved.
 * See more information at http://www.hellomagento2.com
 */

namespace Globeweb\Globepay\Controller\Direct;

use Magento\Framework\App\Action\Action;
use Globeweb\Globepay\Model\Globepay as GlobepayCode;

/**
 * Class Redirect
 */
class Redirect extends Action
{
    /**
     * @var \Globeweb\Globepay\Model\Api\RedirectApi
     */
   // protected $_api;

    /**
     * @var \Globeweb\DeviceDetect\Model\DeviceDetect
     */
   // protected $_detect;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;
    
    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $_quote;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Directory\Model\Currency
     */
    protected $currency;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Checkout\Helper\Data
     */
    protected $_checkoutData;
    /**
     *
     * @var \Magento\Sales\Model\Order
     */
    protected $_orderFactory;
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Directory\Model\Currency $currency,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Helper\Data $checkoutData
    ) {
        parent::__construct($context);
        $this->_orderFactory =$this->_objectManager->get('\Magento\Sales\Model\Order');
        $this->_checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->currency = $currency;
        $this->encryptor = $encryptor;
        $this->_customerSession = $customerSession;
        $this->_checkoutData = $checkoutData;
    }
    public function is_wechat_client(){
        return strripos($_SERVER['HTTP_USER_AGENT'],'micromessenger')!=false;
    }
    
    public function execute()
    {
        try {
            $this->_quote = $this->_checkoutSession->getQuote();
           
            $paymenthod = $this->_quote->getPayment()->getMethodInstance();
            $this->_quote->collectTotals();
    
            if ($this->_quote->getId()) {
                // current currency grand total
                if (!$this->_quote->getGrandTotal()) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __(
                            'Globepay can\'t process orders with a zero balance due. '
                            . 'To finish your purchase, please go through the standard checkout process.'
                        )
                    );
                }
            } else {
                // 页面过期
                $this->_redirect('checkout/onepage/success');
    			return;
            }
           
            $this->_quote->reserveOrderId();
    
            if ($this->getCheckoutMethod() == \Magento\Checkout\Model\Type\Onepage::METHOD_GUEST) {
                $this->prepareGuestQuote();
            }
        
            $this->quoteRepository->save($this->_quote);
    
            $total_fee = $this->getTotalFee();
            // 买家付款时会看到的关键描述信息
            $subject = $this->getSubject();
           
            $order_id_prefix =  $paymenthod->getConfigData('order_id_prefix');
            $out_trade_no = $order_id_prefix.$this->_quote->getId().date('His');
      
            $currency = $this->_quote->getBaseCurrencyCode();
            $credential_code = $paymenthod->getConfigData('md5_key');
            $partner_code = $paymenthod->getConfigData('partner_id');
            $time=time().'000';
             
            $nonce_str = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0,10);
           
            $valid_string="$partner_code&$time&$nonce_str&$credential_code";
            $sign=strtolower(hash('sha256',$valid_string));
            
            if($this->is_wechat_client()){
                $api_uri ='https://pay.globepay.co/api/v1.0/wechat_jsapi_gateway/partners/%s/orders/%s';
            }else{
                $api_uri ='https://pay.globepay.co/api/v1.0/gateway/partners/%s/orders/%s';
            }
        
            $url = sprintf($api_uri,$partner_code,$out_trade_no);
             
            $url.="?time=$time&nonce_str=$nonce_str&sign=$sign";
            $head_arr = array();
            $head_arr[] = 'Content-Type: application/json';
            $head_arr[] = 'Accept: application/json';
            $head_arr[] = 'Accept-Language: en';
             
            $data =new \stdClass();
            $data->description = $subject;
            $data->price = round($total_fee*100);
             
            $data->currency =$currency;
            
             
            $data->notify_url=$paymenthod->getNotifyURL();
             
            $data =json_encode($data);
           
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_PUT, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $head_arr);
            
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt( $ch, CURLOPT_CAINFO, __DIR__ . '/certifivate/ca-bundle.crt');
            
            $temp = tmpfile();
            fwrite($temp, $data);
            fseek($temp, 0);
            curl_setopt($ch, CURLOPT_INFILE, $temp);
            curl_setopt($ch, CURLOPT_INFILESIZE, strlen($data));
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            $response = curl_exec($ch);
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error=curl_error($ch);
            curl_close($ch);
            if($httpStatusCode!=200){
                throw new \Exception("invalid httpstatus:{$httpStatusCode} ,response:$response,detail_error:".$error,$httpStatusCode);
            }
             
            $result =$response;
            
            if($temp){
                fclose($temp);
                unset($temp);
            }
            
            $resArr = json_decode($result,false);
            if(!$resArr){
                throw new \Exception('This request has been rejected by the globepay service!');
            }
            
            if(!isset($resArr->result_code)||$resArr->result_code!='SUCCESS'){
                $errcode =empty($resArr->result_code)?$resArr->return_code:$resArr->result_code;
                throw new \Exception(sprintf('ERROR CODE:%s;ERROR MSG:%s.',$errcode,$resArr->return_msg));
            }
            
            $time=time().'000';
            	
            $nonce_str = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0,10);
            $valid_string="$partner_code&$time&$nonce_str&$credential_code";
            $sign=strtolower(hash('sha256',$valid_string));
            
            $_url =$paymenthod->getReturnURL();
            $_url .=(strpos($_url,'?')===false?'?':'&').'id='.$out_trade_no;
            $url =$resArr->pay_url.(strpos($resArr->pay_url, '?')==false?'?':'&')."directpay=true&time=$time&nonce_str=$nonce_str&sign=$sign&redirect=".urlencode($_url);
            $this->getResponse()->setRedirect($url);
        } catch (\Exception $e) {
            echo $e->getMessage();exit;
            throw $e;
        }
        
    }
    
    
    /**
     * Convert grand total to CNY
     */
    protected function getTotalFee()
    {
        // store base currency code
        
         $total_fee_pre = $this->_quote->getBaseGrandTotal();
        return $total_fee = sprintf('%.2f', $total_fee_pre);
    }

    /**
     * Summary description about items in cart
     *
     * @return string
     */
    protected function getSubject()
    {
        // 商品名称 支付宝要求最多128汉字
        $items = $this->_quote->getAllItems();
        $subject = '';
        foreach ($items as $item) {
            if (strlen($subject) > 64) {
                break;
            }
            $subject = $subject . $item->getName() . '/';
        }
        $subject = mb_substr($subject, 0, 64, 'utf-8');
        $subject = $subject . '... 共'. $this->_quote->getItemsSummaryQty() . '件';
        return $subject;
    }

    /**
     * Get checkout method
     *
     * @return string
     */
    protected function getCheckoutMethod()
    {
        if ($this->_customerSession->isLoggedIn()) {
            return \Magento\Checkout\Model\Type\Onepage::METHOD_CUSTOMER;
        }
        if (!$this->_quote->getCheckoutMethod()) {
            if ($this->_checkoutData->isAllowedGuestCheckout($this->_quote)) {
                $this->_quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_GUEST);
            } else {
                $this->_quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_REGISTER);
            }
        }
        return $this->_quote->getCheckoutMethod();
    }

    /**
     * Prepare quote for guest checkout order submit
     */
    protected function prepareGuestQuote()
    {
        $this->_quote->setCustomerId(null)
            ->setCustomerEmail($this->_quote->getBillingAddress()->getEmail())
            ->setCustomerIsGuest(true)
            ->setCustomerGroupId(\Magento\Customer\Model\Group::NOT_LOGGED_IN_ID);
    }
}
