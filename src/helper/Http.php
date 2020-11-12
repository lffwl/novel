<?php
namespace Lffwl\Novel\helper;

class Http
{

    /**
     * curl小说
     * @param $url
     * @return false|string|string[]|null
     */
    public static function curlNovel($url)
    {
        $header = array(
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.105 Safari/537.36',
        );
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        // 超时设置,以秒为单位
        curl_setopt($curl, CURLOPT_TIMEOUT, 1);
        //表示需要response header
        curl_setopt($curl, CURLOPT_HEADER, true);
        // 超时设置，以毫秒为单位
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);

        // 设置请求头
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        //执行命令
        $data = curl_exec($curl);

        // 显示错误信息
        if (curl_error($curl)) {
            print "Error: " . curl_error($curl);
            exit;
        }

        //对返回的结果进行字符串处理
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);

        curl_close($curl);

        $header = substr($data, 0, $headerSize);
        $body = substr($data, $headerSize);

        //检查是否需要解压
        if (strpos(strtolower($header), 'content-encoding: gzip') !== false) {
            $body = gzdecode($body);
        }

        //检查是否是ut8格式
        if (strpos(strtolower($body), 'charset=utf-8') !== false) {
            return $body;
        }

        $body = mb_convert_encoding($body, "UTF-8", "UTF-8,GBK,GB2312");
        return $body;
    }

}
