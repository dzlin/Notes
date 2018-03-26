<?php

/**
 * 工具函数类
 */

class Util {

    /**
     * 生成唯一编号
     * 可用于生成订单号、支付号等
     *
     * @param integer $member_id
     * @return string
     */
    public function getUniqid($member_id) {
        $microtime = microtime();
        $micro = explode('.', explode(' ', $microtime)[0])[1];
        $time = date('YmdHis', explode(' ', $microtime)[1]);

        $md5_str = md5($time . $member_id . $micro);
        $mem_str = md5($member_id);

        $key = '0123456789abcdefghijklmnopqrstuvwxyz';

        $res1 = '';
        for ($i = 0; $i < 32; ++$i) {
            $res1 .= strpos($key, $md5_str[$i]);
        }

        $res2 = '';
        for ($i = 0; $i < 4; ++$i) {
            $res2 .= strpos($key, $mem_str[$i]);
        }

        return date('s') + 15 . substr($res1, 0, 9) . substr($res2, 0, 4);
    }

    /**
     * 根据指定字段名整理多维数组
     *
     * @param array $data 需整理的多维数组
     * @param string $key 字段名
     * @param boolean $isCover 相同键值是否覆盖
     * @return array|boolean
     */
    static public function arrayChangeKey($data, $key, $isCover = false){
        if (!is_array($data) || is_string($key)){
            return false;
        }
        $result = array();
        foreach($data as $item){
            if($isCover){
                $result[$item[$key]][] = $item;
            }else{
                $result[$item[$key]] = $item;
            }
        }
        return $result;
    }

    /**
     * 递归的对多维数组按键名排序
     * @param array $data 需排序的数组
     * @param integer $sortFlags 排序方式
     * @return array
     */
    static public function arrayKsortRecursive($data, $sortFlags = SORT_STRING) {
        if (is_array($data)) {
            ksort($data, $sortFlags);
            foreach ($data AS $key => $val) {
                $data[$key] = self::arrayKsortRecursive($val, $sortFlags);
            }
        }
        return $data;
    }

    /**
     * 防XSS攻击
     * @param string $val 需要过滤的变量
     * @return string
     */
    static public function removeXSS($val) {
        // remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
        // this prevents some character re-spacing such as <java\0script>
        // note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
        $val = preg_replace('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '', $val);

        // straight replacements, the user should never need these since they're normal characters  
        // this prevents like <IMG SRC=@avascript:alert('XSS')>
        $search = 'abcdefghijklmnopqrstuvwxyz';
        $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $search .= '1234567890!@#$%^&*()';
        $search .= '~`";:?+/={}[]-_|\'\\';
        for ($i = 0; $i < strlen($search); $i++) {
            // ;? matches the ;, which is optional
            // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars
            // @ @ search for the hex values
            $val = preg_replace('/(&#[xX]0{0,8}' . dechex(ord($search[$i])) . ';?)/i', $search[$i], $val); // with a ;
            // @ @ 0{0,7} matches '0' zero to seven times
            $val = preg_replace('/(&#0{0,8}' . ord($search[$i]) . ';?)/', $search[$i], $val); // with a ;
        }

        // now the only remaining whitespace attacks are \t, \n, and \r
        $ra1 = Array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');
        $ra2 = Array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
        $ra = array_merge($ra1, $ra2);

        $found = true; // keep replacing as long as the previous round replaced something
        while ($found == true) {
            $val_before = $val;
            for ($i = 0; $i < sizeof($ra); $i++) {
                $pattern = '/';
                for ($j = 0; $j < strlen($ra[$i]); $j++) {
                    if ($j > 0) {
                        $pattern .= '(';
                        $pattern .= '(&#[xX]0{0,8}([9ab]);)';
                        $pattern .= '|';
                        $pattern .= '|(&#0{0,8}([9|10|13]);)';
                        $pattern .= ')*';
                    }
                    $pattern .= $ra[$i][$j];
                }
                $pattern .= '/i';
                $replacement = substr($ra[$i], 0, 2) . '<x>' . substr($ra[$i], 2); // add in <> to nerf the tag
                $val = preg_replace($pattern, $replacement, $val); // filter out the hex tags
                if ($val_before == $val) {
                    // no replacements were made, so exit the loop
                    $found = false;
                }
            }
        }
        return $val;
    }


    /**
     * 获取变量名称
     *
     * @link http://www.laruence.com/2010/12/08/1716.html
     * @param mixed &$var 要查找的变量
     * @param array $scope 要搜寻的范围
     * @return string 变量名称
     */
    public function getVariableName(&$var, $scope = null) {
        if ($scope == null) {
            $scope = $GLOBALS;
        }
        //因可能有相同值的变量，因此先将当前变量的值保存到一个临时变量中，然后再对原变量赋唯一值，以便查找出变量的名称，找到名字后，将临时变量的值重新赋值到原变量
        $tmp = $var;
        $var = "tmp_exists_" . mt_rand();
        $name = array_search($var, $scope, true); //根据值查找变量名称
        $var = $tmp;
        return $name;
    }

    /**
     * 文件下载
     * 此方法不适合大文件，内存大小限制
     * 可用Web服务器的X-Sendfile模块直接下载文件
     *
     * @param string $file 需要下载的文件路径
     * @param string &$msg 提示信息
     * @param string $fileName 下载文件名
     */
    public function fileDownload($file, &$msg, $fileName = '') {
        $pathInfo = pathinfo($file);
        $filename = $fileName ? $fileName . '.' . $pathInfo['extension'] : $pathInfo['basename']);
        //处理中文文件名
        $ua = $_SERVER["HTTP_USER_AGENT"];
        $encoded_filename = rawurlencode($filename);
        if (preg_match("/MSIE/", $ua)) {
            header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
        } else if (preg_match("/Firefox/", $ua)) {
            header("Content-Disposition: attachment; filename*=\"utf8''" . $filename . '"');
        } else {
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        }

        if (!file_exists($file)) {//判断文件是否存在
            $msg = '文件不存在';
            return false;
        }
        
        $fileSize = filesize($file); //判断文件大小
        //函数filesize()的结果会被缓存
        if ($fileSize === false) {
            $msg = '文件过大或获取文件大小出错';
            return false;
        }
        
        $fp = fopen($file, 'rb'); //下载文件必须先要将文件打开，写入内存
        if ($fp === false) {
            $msg = '文件打开失败';
            return false;
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . $fileName);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));

        //防止服务器瞬时压力增大，分段读取
        $buffer = 1024;
        //如果传递的文件指针无效可能会陷入无限循环中
        while (!feof($fp)) {
            $file_data = fread($fp, $buffer);
            echo $file_data;
        }

        //关闭文件
        fclose($fp);
    }

    /**
     * 自动识别编码（支持GBK/UTF-8）
     * 
     * 编码自动识别的基本思想如下：
     *  1.看给定的字节串是否符合utf8编码规则。如果不符合则为gbk编码。
     *  2.如果给定的字节串中没有符合utf8三字节规则的，则为gbk编码。中文在utf8中占三个字节。
     *  3.如果给定的字节串能对应上gbk编码中的中文，且无法对应上utf8编码中的中文，则为gbk编码。
     *  4.特殊情况，特殊处理。如 “鏈條” 和 “瑷媄”。
     * 总体思想是，先默认为utf8编码，再根据一些非utf8编码的情况逐步筛选。
     * 准确率和效率还是不错的。20万多条query，1秒钟就可以处理完。
     *
     * @param string $str
     * @link http://www.searchtb.com/2014/02/php-auto-detect-encoding.html
     */
    public function detectEncoding($str) {
        $len = strlen($str);
        $encoding = "utf8";
        $is_utf8_chinese = false;
        for ($i = 0; $i < $len; $i++) {
            if ((ord($str[$i]) >> 7) > 0) { //非ascii字符
                if (ord($str[$i]) <= 191) {
                    $encoding = "gbk0";
                    break;
                } else if (ord($str[$i]) <= 223) { //前两位为11
                    if (empty($str[$i + 1]) or ord($str[$i + 1]) >> 6 != 2) { //紧跟后两位为10
                        $encoding = "gbk1";
                        break;
                    } else {
                        $i += 1;
                    }
                } else if (ord($str[$i]) <= 239) { //前三位为111
                    if (empty($str[$i + 1]) or ord($str[$i + 1]) >> 6 != 2 or empty($str[$i + 2]) or ord($str[$i + 2]) >> 6 != 2) { //紧跟后两位为10
                        $encoding = "gbk2";
                        break;
                    } else {
                        $i += 2;
                        $is_utf8_chinese = true;
                    }
                } else if (ord($str[$i]) <= 247) { //前四位为1111
                    if (empty($str[$i + 1]) or ord($str[$i + 1]) >> 6 != 2 or empty($str[$i + 2]) or ord($str[$i + 2]) >> 6 != 2 or empty($str[$i + 3]) or ord($str[$i + 3]) >> 6 != 2) { //紧跟后两位为10
                        $encoding = "gbk3";
                        break;
                    } else {
                        $i += 3;
                    }
                } else if (ord($str[$i]) <= 251) { //前五位为11111
                    if (empty($str[$i + 1]) or ord($str[$i + 1]) >> 6 != 2 or empty($str[$i + 2]) or ord($str[$i + 2]) >> 6 != 2 or empty($str[$i + 3]) or ord($str[$i + 3]) >> 6 != 2 or empty($str[$i + 4]) or ord($str[$i + 4]) >> 6 != 2) { //紧跟后两位为10
                        $encoding = "gbk4";
                        break;
                    } else {
                        $i += 4;
                    }
                } else if (ord($str[$i]) <= 253) { //前六位为111111
                    if (empty($str[$i + 1]) or ord($str[$i + 1]) >> 6 != 2 or empty($str[$i + 2]) or ord($str[$i + 2]) >> 6 != 2 or empty($str[$i + 3]) or ord($str[$i + 3]) >> 6 != 2 or empty($str[$i + 4]) or ord($str[$i + 4]) >> 6 != 2 or empty($str[$i + 5]) or ord($str[$i + 5]) >> 6 != 2) { //紧跟后两位为10
                        $encoding = "gbk5";
                        break;
                    } else {
                        $i += 5;
                    }
                } else {
                    $encoding = "gbk6";
                    break;
                }
            }
        }

        if ($is_utf8_chinese == false) {
            $encoding = "gbk10";
        }
        if ($encoding == "utf8" && preg_match("/^[" . chr(0xa1) . "-" . chr(0xff) . "\x20-\x7f]+$/", $str) && !preg_match("/^[\x{4e00}-\x{9fa5}\x20-\x7f]+$/u", $str)) {
            $encoding = "gbk7";
        }
        //echo $encoding;
        if ($encoding == "utf8") {
            //echo "utf8";
            return ($str == "鏈條" || $str == "瑷媄") ? $str : mb_convert_encoding($str, "gbk", "utf8");
        } else {
            //echo "gbk";
            return $str;
        }
    }

    /**
     * 等宽截取字符串（按汉字宽度）
     *
     * @param string $str
     * @param string $len
     * @param string $encode
     * @param string $start
     * @link http://www.cnblogs.com/picaso/archive/2012/07/31/2616674.html
     */
    public function mbSubstring($str, $len, $encode = 'UTF-8', $start = 0) {
        if (strtoupper($encode) != 'UTF-8') {
            $str = mb_convert_encoding($str, 'UTF-8', $encode);
        }
        $osLen = mb_strlen($str);
        if ($osLen <= $len) {
            return $str;
        }
        $string = mb_substr($str, $start, $len, 'UTF-8');
        $sLen = mb_strlen($string, 'UTF-8');
        $bLen = strlen($string);
        $sCharCount = (3 * $sLen - $bLen) / 2;
        if ($osLen <= $sCharCount + $len) {
            $arr = preg_split('/(?<!^)(?!$)/u', mb_substr($str, $len + 1, $osLen, 'UTF-8'));//将中英混合字符串分割成数组（UTF8下有效）
        } else {
            $arr = preg_split('/(?<!^)(?!$)/u', mb_substr($str, $len + 1, $sCharCount, 'UTF-8'));
        }
        foreach ($arr as $value) {
            if (ord($value) < 128 && ord($value) > 0) {
                $sCharCount = $sCharCount - 1;
            } else {
                $sCharCount = $sCharCount - 2;
            }
            if ($sCharCount <= 0) {
                break;
            }
            $string .= $value;
        }
        return $string;
    }
    
}
