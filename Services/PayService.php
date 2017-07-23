<?php
/**
 * Created by PhpStorm.
 * User: Lizheng
 * Date: 2017/7/22
 * Time: 21:06
 */

namespace Liz\AliBundle\Services;


use Liz\AliBundle\Utils\Tool;

class PayService
{
    private static $payGateWay = 'https://openapi.alipay.com/gateway.do';

    private static $appId;

    private static $accessKeyId;

    private static $accessKeySecret;

    private $tool;

    public function __construct($env, $accessKeyId, $accessKeySecret,
                                Tool $tool, $cacheDir, $appId)
    {
        $this->accessKeyId = $accessKeyId;
        $this->accessKeySecret = $accessKeySecret;
        $this->tool = $tool;
        $this->init($env, $cacheDir);
    }

    protected function init($env, $cacheDir){
        define("AOP_SDK_WORK_DIR", $cacheDir);
        if($env=='prod'){
            define("AOP_SDK_DEV_MODE", false);
        }else{
            define("AOP_SDK_DEV_MODE", true);
        }
        /**
         * 找到lotusphp入口文件，并初始化lotusphp
         * lotusphp是一个第三方php框架，其主页在：lotusphp.googlecode.com
         */
        $aliPayDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR .
            "AliPay"  . DIRECTORY_SEPARATOR ;
        $lotusHome =  $aliPayDir . "lotusphp_runtime" . DIRECTORY_SEPARATOR;
        include($lotusHome . "Lotus.php");
        $lotus = new \Lotus;
        $lotus->option["autoload_dir"] = $aliPayDir . 'aop';
        $lotus->devMode = AOP_SDK_DEV_MODE;
        $lotus->defaultStoreDir = AOP_SDK_WORK_DIR;
        $lotus->init();
    }

    protected function AopClientInit($postCharset="UTF-8", $format="json"){
        $aop = new \AopClient ();
        $aop->gatewayUrl = self::$payGateWay;
        $aop->appId = self::$appId;
        $aop->rsaPrivateKey = '请填写开发者私钥去头去尾去回车，一行字符串';
        $aop->alipayrsaPublicKey='请填写支付宝公钥，一行字符串';
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset=$postCharset;
        $aop->format=$format;
        return $aop;
    }

    public function pay($postBody, $postCharset="UTF-8", $format="json"){
        $request = new \AlipayTradePayRequest ();
        $request->setBizContent("{" .
            "\"out_trade_no\":\"20150320010101001\"," .
            "\"scene\":\"bar_code\"," .
            "\"auth_code\":\"28763443825664394\"," .
            "\"product_code\":\"FACE_TO_FACE_PAYMENT\"," .
            "\"subject\":\"Iphone6 16G\"," .
            "\"buyer_id\":\"2088202954065786\"," .
            "\"seller_id\":\"2088102146225135\"," .
            "\"total_amount\":88.88," .
            "\"discountable_amount\":8.88," .
            "\"undiscountable_amount\":80.00," .
            "\"body\":\"Iphone6 16G\"," .
            "      \"goods_detail\":[{" .
            "        \"goods_id\":\"apple-01\"," .
            "\"alipay_goods_id\":\"20010001\"," .
            "\"goods_name\":\"ipad\"," .
            "\"quantity\":1," .
            "\"price\":2000," .
            "\"goods_category\":\"34543238\"," .
            "\"body\":\"特价手机\"," .
            "\"show_url\":\"http://www.alipay.com/xxx.jpg\"" .
            "        }]," .
            "\"operator_id\":\"yx_001\"," .
            "\"store_id\":\"NJ_001\"," .
            "\"terminal_id\":\"NJ_T_001\"," .
            "\"alipay_store_id\":\"2016041400077000000003314986\"," .
            "\"extend_params\":{" .
            "\"sys_service_provider_id\":\"2088511833207846\"," .
            "\"hb_fq_num\":\"3\"," .
            "\"hb_fq_seller_percent\":\"100\"" .
            "    }," .
            "\"timeout_express\":\"90m\"," .
            "\"royalty_info\":{" .
            "\"royalty_type\":\"ROYALTY\"," .
            "        \"royalty_detail_infos\":[{" .
            "          \"serial_no\":1," .
            "\"trans_in_type\":\"userId\"," .
            "\"batch_no\":\"123\"," .
            "\"out_relation_id\":\"20131124001\"," .
            "\"trans_out_type\":\"userId\"," .
            "\"trans_out\":\"2088101126765726\"," .
            "\"trans_in\":\"2088101126708402\"," .
            "\"amount\":0.1," .
            "\"desc\":\"分账测试1\"," .
            "\"amount_percentage\":\"100\"" .
            "          }]" .
            "    }," .
            "\"sub_merchant\":{" .
            "\"merchant_id\":\"19023454\"" .
            "    }," .
            "\"disable_pay_channels\":\"credit_group\"," .
            "\"ext_user_info\":{" .
            "\"name\":\"李明\"," .
            "\"mobile\":\"16587658765\"," .
            "\"cert_type\":\"IDENTITY_CARD\"," .
            "\"cert_no\":\"362334768769238881\"," .
            "\"fix_buyer\":\"F\"" .
            "    }" .
            "  }");

        $result = $this->AopClientInit()->execute( $request);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if(!empty($resultCode)&&$resultCode == 10000){
            echo "成功";
        } else {
            echo "失败";
        }
    }
}