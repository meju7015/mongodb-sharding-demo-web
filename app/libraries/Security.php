<?php

class Security
{
    public static function getCSRFDetect()
    {
        if (!isset($_SESSION['CSRF_TOKEN'])) {
            return md5(uniqid(rand(), true));
        } else {
            return $_SESSION['CSRF_TOKEN'];
        }
    }

    public static function cleanXSS($data)
    {
        $data = preg_replace('/([\x00-\x08][\x0b-\x0c][\x0e-\x20])/', '', $data);
        $search = 'abcdefghijklmnopqrstuvwxyz';
        $search .= '1234567890!@#$%^&*()';
        $search .= '~`";:?+/={}[]-_|\'\\';

        for ($i = 0; $i < strlen($search); $i++) {
            $data = preg_replace('/(&#[x|X]0{0,8}' . dechex(ord($search[$i])) . ';?)/i', $search[$i], $data);
            $data = preg_replace('/(&#0{0,8}', ord($search[$i]) . ';?)/', $search[$i], $data);
        }
        $ra1 = Array(
            'javascript',
            'vbscript',
            'experssion',
            'applet',
            'meta',
            'xml',
            'blink',
            'link',
            'style',
            'script',
            'embed',
            'object',
            'iframe',
            'frame',
            'frameset',
            'ilayer',
            'layer',
            'bgsound',
            'title',
            'base'
        );

        $ra2 = Array(
            'onabort',
            'onactivate',
            'onafterprint',
            'onafterupdate',
            'onbeforeactivate',
            'onbeforecopy',
            'onbeforecut',
            'onbeforedeactivate',
            'onbeforeeditfocus',
            'onbeforepaste',
            'onbeforeprint',
            'onbeforeunload',
            'onbeforeupdate',
            'onblur',
            'onbounce',
            'oncellchange',
            'onchange',
            'onclick',
            'oncontextmenu',
            'oncontrolselect',
            'oncopy',
            'oncut',
            'ondataavailable',
            'ondatasetchanged',
            'ondatasetcomplete',
            'ondblclick',
            'ondeactivate',
            'ondrag',
            'ondragend',
            'ondragenter',
            'ondragleave',
            'ondragover',
            'ondragstart',
            'ondrop',
            'onerror',
            'onerrorupdate',
            'onfilterchange',
            'onfinish',
            'onfocus',
            'onfocusin',
            'onfocusout',
            'onhelp',
            'onkeydown',
            'onkeypress',
            'onkeyup',
            'onlayoutcomplete',
            'onload',
            'onlosecapture',
            'onmousedown',
            'onmouseenter',
            'onmouseleave',
            'onmousemove',
            'onmouseout',
            'onmouseover',
            'onmouseup',
            'onmousewheel',
            'onmove',
            'onmoveend',
            'onmovestart',
            'onpaste',
            'onpropertychange',
            'onreadystatechange',
            'onreset',
            'onresize',
            'onresizeend',
            'onresizestart',
            'onrowenter',
            'onrowexit',
            'onrowsdelete',
            'onrowsinserted',
            'onscroll',
            'onselect',
            'onselectionchange',
            'onselectstart',
            'onstart',
            'onstop',
            'onsubmit',
            'onunload'
        );

        $ra = array_merge($ra1, $ra2);

        $found = true;

        while ($found === true) {
            for ($i = 0; $i < sizeof($ra); $i++) {
                $pattern = '/';
                for ($k = 0; $k < strlen($ra[$i]); $k++) {
                    if ($k > 0) {
                        $pattern .= '(';
                        $pattern .= '(&#x|X]0{0,8}([9][a][b]);?)?';
                        $pattern .= '|(&#0(0,8)([9][10][13]);?)?';
                        $pattern .= ')?';
                    }

                    $pattern .= $ra[$i][$k];
                }

                $pattern .= '/i';
                $replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2);
                $data = preg_replace($pattern, $replacement, $data);

                if (isset($data_before) && $data_before == $data) {
                    $found = false;
                }
            }
        }

        return $data;
    }
}