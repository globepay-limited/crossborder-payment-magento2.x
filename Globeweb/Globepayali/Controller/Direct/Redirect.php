<?php
/**
 * Copyright © 2016 Globeweb. All rights reserved.
 * See more information at http://www.hellomagento2.com
 */

namespace Globeweb\Globepayali\Controller\Direct;

use Magento\Framework\App\Action\Action;
use Globeweb\Globepayali\Model\Globepayali as GlobepayaliCode;

/**
 * Class Redirect
 */
class Redirect extends Action
{
    /**
     * @var \Globeweb\Globepayali\Model\Api\RedirectApi
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
    protected $assetRepo;

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
        $this->assetRepo =$this->_objectManager->get('\Magento\Framework\View\Asset\Repository');
    }
    public function is_wechat_client(){
        return strripos($_SERVER['HTTP_USER_AGENT'],'micromessenger')!=false;
    }
    public  function isIOS(){
	    $ua =$_SERVER['HTTP_USER_AGENT'];
	    return strripos($ua,'iphone')!=false||strripos($ua,'ipad')!=false;
	}
	public function is_app_client(){
	    if(!isset($_SERVER['HTTP_USER_AGENT'])){
	        return false;
	    }

	    $u=strtolower($_SERVER['HTTP_USER_AGENT']);
	    if($u==null||strlen($u)==0){
	        return false;
	    }

	    preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/',$u,$res);

	    if($res&&count($res)>0){
	        return true;
	    }

	    if(strlen($u)<4){
	        return false;
	    }

	    preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/',substr($u,0,4),$res);
	    if($res&&count($res)>0){
	        return true;
	    }

	    $ipadchar = "/(ipad|ipad2)/i";
	    preg_match($ipadchar,$u,$res);
	    if($res&&count($res)>0){
	        return true;
	    }

	    return false;
	}
    public function execute()
    {

        if($this->is_wechat_client()){
            ?>
            <html>
                <head>
                <meta http-equiv="content-type" content="text/html;charset=utf-8" />
                <meta name="viewport" content="width=device-width, initial-scale=1" />
                <title>支付宝</title>
                <body style="padding:0;margin:0;">
                <?php
                	if($this->isIOS()){
                		?>
                		<img alt="支付宝" src="<?php echo $this->assetRepo->getUrl('Globeweb_Globepayaliali::images/ios.png')?>" style="max-width: 100%;">
                		<?php
                	}else{
                		?>
                		<img alt="支付宝" src="<?php echo $this->assetRepo->getUrl('Globeweb_Globepayaliali::images/alipayout.jpg')?>" style="max-width: 100%;">
                		<?php
                	}
                ?>
                </body>
                </html>
            <?php
            return;
        }
        try {
            $this->_quote = $this->_checkoutSession->getQuote();
            $order = $this->_orderFactory->loadByIncrementId( $this->_quote->getReservedOrderId());
            if($order->getId()){

            }

            $paymenthod = $this->_quote->getPayment()->getMethodInstance();
            $this->_quote->collectTotals();

            if ($this->_quote->getId()) {
                // current currency grand total
                if (!$this->_quote->getGrandTotal()) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __(
                            'Globepayali can\'t process orders with a zero balance due. '
                            . 'To finish your purchase, please go through the standard checkout process.'
                        )
                    );
                }
            } else {
                // 页面过期
                $this->_redirect('checkout/cart/');
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

            $channel=null;
            if($this->is_app_client()){
                $channel='Alipay';
                $api_uri ='https://pay.globepay.co/api/v1.0/h5_payment/partners/%s/orders/%s';
            }else{
                $channel='Alipay';
                $api_uri ='https://pay.globepay.co/api/v1.0/web_gateway/partners/%s/orders/%s';
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
            if($channel){
                $data->channel = $channel;
            }

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
            $this->messageManager->addError("订单支付失败! :".$e->getMessage());
             $this->_redirect('checkout/cart');
    			return;
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
