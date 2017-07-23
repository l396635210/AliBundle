<?php
/**
 * Created by PhpStorm.
 * User: Lizheng
 * Date: 2017/7/22
 * Time: 6:55
 */

namespace Liz\AliBundle\Utils;

use Symfony\Component\Translation\TranslatorInterface;

class Tool
{

    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function trans($message, array $params = array())
    {
        return $this->translator->trans($message, $params, 'LizAliBundle');
    }

}