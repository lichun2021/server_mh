<?php


namespace App\Services;

use App\SensitiveWord;
use DfaFilter\SensitiveHelper;

/**
 * 敏感词过滤
 * Class SensitiveWordsService
 * @package App\Services
 * @author 春风 <860646000@qq.com>
 */
class SensitiveWordsService
{
    protected static $handle = null;

    /**
     * 单列
     * @return SensitiveHelper|null
     * @throws \DfaFilter\Exceptions\PdsBusinessException
     */
    protected static function getInstance()
    {
        self::$handle = SensitiveHelper::init();
        self::$handle->setTree(SensitiveWord::getWords());
        return self::$handle;
    }

    /**
     * 检测是否含有敏感词
     * @param $content
     * @return bool
     * @throws \DfaFilter\Exceptions\PdsBusinessException
     * @throws \DfaFilter\Exceptions\PdsSystemException
     */
    public static function isLegal($content)
    {
        return self::getInstance()->islegal($content);
    }

    /**
     * 获取文字中的敏感词
     * @param $content
     * @param int $match_type
     * @param int $word_num
     * @return array
     * @throws \DfaFilter\Exceptions\PdsBusinessException
     * @throws \DfaFilter\Exceptions\PdsSystemException
     */
    public static function getBadWord($content, $match_type = 1, $word_num = 0)
    {
        return self::getInstance()->getBadWord($content, $match_type, $word_num);
    }

    /**
     * 标记敏感词
     * @param $content
     * @param $start_tag
     * @param $end_tag
     * @param int $match_type
     * @return mixed
     * @throws \DfaFilter\Exceptions\PdsBusinessException
     * @throws \DfaFilter\Exceptions\PdsSystemException
     */
    public static function mark($content, $start_tag, $end_tag, $match_type = 1)
    {
        return self::getInstance()->mark($content, $start_tag, $end_tag, $match_type);
    }

    /**
     * 敏感词过滤
     * @param $content
     * @param string $replace_char
     * @param bool $repeat
     * @param int $match_type
     * @return SensitiveHelper|null
     * @throws \DfaFilter\Exceptions\PdsBusinessException
     */
    public static function replace($content, $replace_char = '', $repeat = false, $match_type = 1)
    {
        return self::getInstance()- replace($content, $replace_char, $repeat, $match_type);
    }
}
