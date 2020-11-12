<?php

namespace Lffwl\Novel;

use Lffwl\Novel\helper\Http;
use Lffwl\Novel\helper\Route;

class NovelRead
{

    protected $errorMsg;

    /**
     * 获取小说章节详细信息
     * @param $url 章节地址
     * @return array|false
     */
    public function toRead($url)
    {
        $result = [];
        $content = Http::curlNovel($url);

        //获取上一,下一章
        preg_match_all("/<a .*\>.*<\/a>/U", $content, $aArray);
        foreach ($aArray[0] as $val) {
            if (strpos($val, '下页') !== false) {
                $result['next'] = self::getChapterLink($val, $url);
            }
            if (strpos($val, '下一') !== false) {
                $result['next'] = self::getChapterLink($val, $url);
            }

            if (strpos($val, '上页') !== false) {
                $result['upper'] = self::getChapterLink($val, $url);
            }
            if (strpos($val, '上一') !== false) {
                $result['upper'] = self::getChapterLink($val, $url);
            }

            if (strpos($val, '目录') !== false) {
                $result['catalog'] = self::getChapterLink($val, $url);
            }

        }

        //如果目录和下章节相同就去掉下一章
        if ($result['catalog'] == $result['next']) {
            unset($result['next']);
        }

        //获取章节标题
        preg_match_all('/<title>(.*)<\/title>/i', $content, $titleArr);
        $result['title'] = '未获取到章节名称';
        if (!empty($titleArr[0][0])) {
            $result['title'] = $titleArr[0][0];
        }

        if (!empty($titleArr[1][0])) {
            $result['title'] = $titleArr[1][0];
        }

        //获取正文内容
        preg_match_all('/<div id="[\s\S]*?<\/div>/i', $content, $arrContent);
        $contentLength = 0;
        foreach ($arrContent[0] as $val) {
            if ($contentLength < mb_strlen($val)) {
                $contentLength = mb_strlen($val);
                $result['content'] = $val;
            }
        }

        if (!empty($result['content'])) {
            //检查正文中是否有script ，如果有就替换
            if (strpos(strtolower($result['content']), '<script') !== false) {
                $result['content'] = preg_replace("/<script[\s\S]*?<\/script>/i", "", $result['content']);
            }

            //检查正文中是否有div ，如果有就替换
            if (strpos(strtolower($result['content']), '<div') !== false) {
                $result['content'] = preg_replace("/<div[\s\S]*?>/i", "", $result['content']);
            }

            //检查正文中是否有<!-???-> ，如果有就替换
            if (strpos(strtolower($result['content']), '<!-') !== false) {
                $result['content'] = preg_replace("/<!-[\s\S]*?->/i", "", $result['content']);
            }
        } else {
            $this->errorMsg = '页面不存在小说内容';
            return false;
        }
        return $result;
    }

    /**
     * 获取章节的链接
     * @param $str
     * @param $url
     * @return string
     */
    protected static function getChapterLink($str, $url)
    {
        preg_match('/(?<=href=")[\w\d\.:\/]*/', $str, $link);
        $t = '';
        if (!empty($link)) {
            $t = $link[0];
            $t = self::getLink($t, $url);
        }
        return $t;
    }

    /**
     * 链接问题
     * @param $link
     * @param $url
     * @return string
     */
    protected static function getLink($link, $url)
    {
        $t = $link;
        if (substr($link, 0, 1) == '/') {
            $t = Route::getUrlDomainName($url) . $link;
        } else if (!Route::checkUrlIsExistDomain($link)) {
            $t = Route::getCompleteUrl($url, $link);
        }
        return $t;
    }


    /**
     * 小说的章节目录列表
     * @param $url 章节目录地址
     * @return array
     */
    public function catalog($url)
    {
        $result = [];
        $domain = Route::getUrlDomainName($url);
        //目录链接关键字
        $key = str_replace($domain . '/', "", $url);
        if (strpos($key, '_') !== false) {
            //多级目录去掉后缀匹配
            $key = substr($key, 0, strrpos($key, '_'));
        }

        $content = Http::curlNovel($url);
        preg_match_all('/<a .*?\>.*?<\/a>/sim', $content, $aArray);

        foreach ($aArray[0] as $val) {
            //去掉空格
            $val = str_replace(' ', "", $val);
            //单引号问题处理
            $val = str_replace("'", '"', $val);
            //匹配出想要的信息
            preg_match_all('/<a.*?(?: |\\t|\\r|\\n)?href=[\'"]?(.+?)[\'"]?(?:(?: |\\t|\\r|\\n)+.*?)?>(.+?)<\/a.*?>/sim', $val, $aInfo);

            //检查是否是章节链接
            if (!empty($aInfo[2][0])) {
                if (
                    strpos($aInfo[2][0], '章') !== false &&
                    strpos($aInfo[2][0], '开始阅读') === false &&
                    strpos($aInfo[2][0], '倒序') === false &&
                    strpos($aInfo[2][0], '正序') === false &&
                    strpos($aInfo[2][0], '首页') === false &&
                    strpos($aInfo[2][0], '上一页') === false &&
                    strpos($aInfo[2][0], '下一页') === false &&
                    strpos($aInfo[2][0], '尾页') === false
                ) {
                    $tempUrl = self::getLink($aInfo[1][0], $url);
                    //去掉a标签中其他元素
                    if (strpos($tempUrl, 'html"') !== false) {
                        $tempUrl = substr($tempUrl, 0, strrpos($tempUrl, 'html"') + 4);
                    }
                    $result['data'][] = [
                        'url' => $tempUrl,
                        'title' => strip_tags($aInfo[2][0]),//去掉标题中的html标签
                    ];
                }
            }

            //获取下一页路径
            if (strpos($val, '下页') !== false) {
                $result['next'] = self::getChapterLink($val, $url);
            }
            if (strpos($val, '下一页') !== false) {
                $result['next'] = self::getChapterLink($val, $url);
            }
        }
        $result['data'] = array_values(array_unique($result['data'], SORT_REGULAR));

        return $result;
    }

    /**
     * 获取错误信息
     * @return mixed
     */
    public function getError()
    {
        return $this->errorMsg;
    }
}
