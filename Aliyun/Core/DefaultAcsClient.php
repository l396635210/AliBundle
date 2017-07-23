<?php

namespace Liz\AliBundle\Aliyun\Core;

use Liz\AliBundle\Aliyun\Core\Exception\ClientException;
use Liz\AliBundle\Aliyun\Core\Exception\ServerException;
use Liz\AliBundle\Aliyun\Core\Regions\EndpointProvider;
use Liz\AliBundle\Aliyun\Core\Http\HttpHelper;
use Liz\AliBundle\Utils\Tool;

class DefaultAcsClient implements IAcsClient 
{    
    public $iClientProfile;
    public $__urlTestFlag__;
    private $tool;
    
    function  __construct($iClientProfile, Tool $tool)
    {
        $this->iClientProfile = $iClientProfile;
        $this->__urlTestFlag__ = false;
        $this->tool = $tool;
    }
    
    public function getAcsResponse($request, $iSigner = null, $credential = null, $autoRetry = true, $maxRetryNumber = 3)
    {
        $httpResponse = $this->doActionImpl($request, $iSigner, $credential, $autoRetry, $maxRetryNumber);
        $respObject = $this->parseAcsResponse($httpResponse->getBody(), $request->getAcceptFormat());
        if(false == $httpResponse->isSuccess())
        {
            $this->buildApiException($respObject, $httpResponse->getStatus());
        }
        return $respObject;
    }

    private function doActionImpl($request, $iSigner = null, $credential = null, $autoRetry = true, $maxRetryNumber = 3)
    {    
        if(null == $this->iClientProfile && (null == $iSigner || null == $credential 
            || null == $request->getRegionId() || null == $request->getAcceptFormat()))
        {
            throw new ClientException("No active profile found.", "SDK.InvalidProfile");
        }
        if(null == $iSigner)
        {
            $iSigner = $this->iClientProfile->getSigner();
        }
        if(null == $credential)
        {
            $credential = $this->iClientProfile->getCredential();
        }
        $request = $this->prepareRequest($request);
        $domain = EndpointProvider::findProductDomain($request->getRegionId(), $request->getProduct());

        if(null == $domain)
        {
            throw new ClientException("Can not find endpoint to access.", "SDK.InvalidRegionId");
        }
        $requestUrl = $request->composeUrl($iSigner, $credential, $domain);

        if ($this->__urlTestFlag__) {
            throw new ClientException($requestUrl, "URLTestFlagIsSet");
        }

        if(count($request->getDomainParameter())>0){
            $httpResponse = HttpHelper::curl($requestUrl, $request->getMethod(), $request->getDomainParameter(), $request->getHeaders());
        } else {
            $httpResponse = HttpHelper::curl($requestUrl, $request->getMethod(),$request->getContent(), $request->getHeaders());
        }
        
        $retryTimes = 1;
        while (500 <= $httpResponse->getStatus() && $autoRetry && $retryTimes < $maxRetryNumber) {
            $requestUrl = $request->composeUrl($iSigner, $credential,$domain);
            
            if(count($request->getDomainParameter())>0){
                $httpResponse = HttpHelper::curl($requestUrl, $request->getDomainParameter(), $request->getHeaders());
            } else {
                $httpResponse = HttpHelper::curl($requestUrl, $request->getMethod(), $request->getContent(), $request->getHeaders());
            }
            $retryTimes ++;
        }
        return $httpResponse;
    }
    
    public function doAction($request, $iSigner = null, $credential = null, $autoRetry = true, $maxRetryNumber = 3)
    {    
        trigger_error("doAction() is deprecated. Please use getAcsResponse() instead.", E_USER_NOTICE);
        return $this->doActionImpl($request, $iSigner, $credential, $autoRetry, $maxRetryNumber);
    }
    
    private function prepareRequest($request)
    {
        if(null == $request->getRegionId())
        {
            $request->setRegionId($this->iClientProfile->getRegionId());
        }
        if(null == $request->getAcceptFormat())
        {
            $request->setAcceptFormat($this->iClientProfile->getFormat());
        }
        if(null == $request->getMethod())
        {
            $request->setMethod("GET");
        }
        return $request;
    }
    
    
    private function buildApiException($respObject, $httpStatus)
    {
        throw new ServerException($this->tool->trans($respObject->Message),
            $this->tool->trans($respObject->Code),
            $httpStatus, $respObject->RequestId);
    }
    
    private function parseAcsResponse($body, $format)
    {
        if ("JSON" == $format)
        {    
            $respObject = json_decode($body);
        }
        else if("XML" == $format)
        {
            $respObject = @simplexml_load_string($body);
        }
        else if("RAW" == $format)
        {
            $respObject = $body;
        }
        return $respObject;
    }
}
