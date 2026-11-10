<?php
/*
 * File: CommonController.php
 * Created Time: 2023-11-14 3:28:20 pm
 * Author: Wang Yu Qiang (719404171@qq.com)
 * -----
 * Last Modified: 2024-05-30 4:15:49 pm
 * Modified By: WangYuQiang && WangpeiGang (719404171@qq.com>)
 * -----
 * Copyright 2023 - 2023 Brainstorm Ltd, 成都睿創科技有限公司
 */
namespace App\Http\Controllers\Common;
use Illuminate\Http\Request;
/*
* $text = '中國人中国人123abc';
*
* 转为简体
* var_dump(Chinese::simplified($text)); // 中国人中国人123abc
*
* 转为繁体
* var_dump(Chinese::traditional($text)); // 中國人中國人123abc
*
* 转为以多字节字符串分隔的数组
* var_dump(Chinese::toArray($text));
*
* 判断是否全部为中文
* var_dump(Chinese::isChinese($text)); // bool(false)
*
* 判断是否包含中文
* var_dump(Chinese::hasChinese($text)); // bool(true)
*
*/
class ChineseController 
{

    public static $simplifiedKey;
    public static $traditionalKey;

    private static function init()
    {
        if (static::$traditionalKey && static::$simplifiedKey) {
            return;
        }

        static::$traditionalKey = require_once __DIR__.'/traditionalchinese.php';
        static::$simplifiedKey = array_flip(static::$traditionalKey);
    }

    /**
     * 转化为简体
     *
     * @param $text
     * @return string
     */
    public static function simplified($text)
    {
        return static::reverse($text, true);
    }

    /**
     * 转化为繁体
     *
     * @param $text
     * @return string
     */
    public static function traditional($text)
    {
        return static::reverse($text, false);
    }

    private static function reverse(string $text, bool $toSimplified = false)
    {
        static::init();

        $result = '';

        $words = static::toArray($text);

        foreach ($words as $word) {
            if (static::isChinese($word)) {
                $word = ($toSimplified ? (static::$traditionalKey[$word] ?? $word) : (static::$simplifiedKey[$word] ?? $word));
            }
            $result .= $word;
        }

        return $result;
    }

    /**
     * 转化中文至字符串
     *
     * @param string $text
     * @return array
     */
    public static function toArray(string $text)
    {
        $len = $index = mb_strlen($text);

        $result = [];

        while ($index > 0) {
            $result[] = mb_substr($text, $len - $index, 1);
            --$index;
        }

        return $result;
    }

    /**
     * 判断是否中文
     *
     * @param string $text
     * @return bool
     */
    public static function isChinese(string $text)
    {
        return preg_match('/^[\x{4e00}-\x{9fa5}]+$/u', $text) > 0;
    }

    /**
     * 判断是否包含中文
     *
     * @param string $text
     * @return bool
     */
    public static function hasChinese(string $text)
    {
        return preg_match('/[\x{4e00}-\x{9fa5}]/u', $text) > 0;
    }
}
 