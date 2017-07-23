<?php

namespace Liz\AliBundle\Aliyun\Core\Auth;

interface ISigner
{
	public function  getSignatureMethod();
	
	public function  getSignatureVersion();
	
	public function signString($source, $accessSecret); 
}