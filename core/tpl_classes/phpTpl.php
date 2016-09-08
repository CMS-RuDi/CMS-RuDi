<?php
/******************************************************************************/
//                                                                            //
//                           InstantCMS v1.10.6                               //
//                        http://www.instantcms.ru/                           //
//                                                                            //
//                   written by InstantCMS Team, 2007-2015                    //
//                produced by InstantSoft, (www.instantsoft.ru)               //
//                                                                            //
//                        LICENSED BY GNU/GPL v2                              //
//                                                                            //
/******************************************************************************/
/**
 * Класс для работы с php шаблонами
 */
class phpTpl extends tplMainClass
{
    private static $cycle_vars;
    
    protected function initTemplateEngine()
    {
        self::$tpl = \cmsPage::getInstance();
    }

    public function display()
    {
        global $_LANG;
        
        extract($this->tpl_vars);
        
        $is_ajax  = \cmsCore::isAjax();
        $is_auth  = \cmsUser::getInstance()->id;
        $user_id  = \cmsUser::getInstance()->id;
        $is_admin = \cmsUser::getInstance()->is_admin;
        
        $inConf   = \cmsCore::c('config')->getConfig();

        if (file_exists(TEMPLATE_DIR . $this->tpl_file))
        {
            include(TEMPLATE_DIR . $this->tpl_file);
        }
        else
        {
            include(DEFAULT_TEMPLATE_DIR . $this->tpl_file);
        }
    }
    
    public function fetch()
    {
        ob_start();
            $this->display();
        return ob_get_clean();
    }
    
    //==========================================================================
    
    public function truncate($string, $length = 80, $etc = '...', $break_words = false, $middle = false)
    {
        if ($length == 0) {
            return '';
        }

        if (mb_strlen($string) > $length)
        {
            $length -= min($length, mb_strlen($etc));
            
            if (!$break_words && !$middle) {
                $string = preg_replace('/\s+?(\S+)?$/u', '', mb_substr($string, 0, $length+1));
            }
            
            if (!$middle)
            {
                return mb_substr($string, 0, $length) . $etc;
            }
            else
            {
                return mb_substr($string, 0, $length/2) . $etc . mb_substr($string, -$length/2);
            }
        }
        else
        {
            return $string;
        }
    }
    
    public function rating($rating)
    {
        if ($rating == 0)
        {
            $html = '<span style="color:gray;">0</span>';
	}
        else if ($rating > 0)
        {
            $html = '<span style="color:green">+'.$rating.'</span>';
	}
        else
        {
            $html = '<span style="color:red">'.$rating.'</span>';
	}
        
	return $html;
    }
    
    public function escape($string, $esc_type = 'html', $char_set = 'UTF-8')
    {
        switch ($esc_type) {
            case 'html':
                return htmlspecialchars($string, ENT_QUOTES, $char_set);
            case 'htmlall':
                return htmlentities($string, ENT_QUOTES, $char_set);
            case 'url':
                return rawurlencode($string);
            case 'urlpathinfo':
                return str_replace('%2F','/',rawurlencode($string));
            case 'quotes':
                // escape unescaped single quotes
                return preg_replace("%(?<!\\\\)'%u", "\\'", $string);
            case 'hex':
                // escape every character into hex
                $return = '';
                for ($x=0; $x < mb_strlen($string); $x++) {
                    $return .= '%' . bin2hex($string[$x]);
                }
                return $return;
            case 'hexentity':
                $return = '';
                for ($x=0; $x < mb_strlen($string); $x++) {
                    $return .= '&#x' . bin2hex($string[$x]) . ';';
                }
                return $return;
            case 'decentity':
                $return = '';
                for ($x=0; $x < mb_strlen($string); $x++) {
                    $return .= '&#' . ord($string[$x]) . ';';
                }
                return $return;
            case 'javascript':
                // escape quotes and backslashes, newlines, etc.
                return strtr($string, array('\\'=>'\\\\',"'"=>"\\'",'"'=>'\\"',"\r"=>'\\r',"\n"=>'\\n','</'=>'<\/'));
            case 'mail':
                // safe way to display e-mail address on a web page
                return str_replace(array('@', '.'),array(' [AT] ', ' [DOT] '), $string);
            case 'nonstd':
                // escape non-standard chars, such as ms document quotes
                $_res = '';
                for ($_i = 0, $_len = mb_strlen($string); $_i < $_len; $_i++) {
                    $_ord = ord(mb_substr($string, $_i, 1));
                    // non-standard char, escape it
                    if ($_ord >= 126)
                    {
                        $_res .= '&#' . $_ord . ';';
                    }
                    else
                    {
                        $_res .= mb_substr($string, $_i, 1);
                    }
                }
                return $_res;

            default:
                return $string;
        }
    }
    
    public function spellcount($num, $one, $two, $many, $is_full = true)
    {
        return \cmsCore::spellCount($num, $one, $two, $many, $is_full);
    }
    
    public function cycle($params)
    {
        $name    = (empty($params['name']))    ? 'default'                : $params['name'];
        $print   = (isset($params['print']))   ? (bool)$params['print']   : true;
        $advance = (isset($params['advance'])) ? (bool)$params['advance'] : true;
        $reset   = (isset($params['reset']))   ? (bool)$params['reset']   : false;

        if (!in_array('values', array_keys($params)))
        {
            if (!isset(self::$cycle_vars[$name]['values'])) {
                cmsCore::addSessionMessage("cycle: missing 'values' parameter", 'error');
                return;
            }
        }
        else
        {
            if (isset(self::$cycle_vars[$name]['values']) && self::$cycle_vars[$name]['values'] != $params['values'] ) {
                self::$cycle_vars[$name]['index'] = 0;
            }
            
            self::$cycle_vars[$name]['values'] = $params['values'];
        }

        if (isset($params['delimiter']))
        {
            self::$cycle_vars[$name]['delimiter'] = $params['delimiter'];
        }
        else if (!isset(self::$cycle_vars[$name]['delimiter']))
        {
            self::$cycle_vars[$name]['delimiter'] = ',';       
        }

        if (is_array(self::$cycle_vars[$name]['values']))
        {
            $cycle_array = self::$cycle_vars[$name]['values'];
        }
        else
        {
            $cycle_array = explode(self::$cycle_vars[$name]['delimiter'],self::$cycle_vars[$name]['values']);
        }

        if (!isset(self::$cycle_vars[$name]['index']) || $reset ) {
            self::$cycle_vars[$name]['index'] = 0;
        }

        if ($print)
        {
            $retval = $cycle_array[self::$cycle_vars[$name]['index']];
        }
        else
        {
            $retval = null;
        }

        if ($advance) {
            if (self::$cycle_vars[$name]['index'] >= (count($cycle_array) - 1))
            {
                self::$cycle_vars[$name]['index'] = 0;
            }
            else
            {
                self::$cycle_vars[$name]['index']++;
            }
        }

        return $retval;
    }
    
    public function strip_tags($string, $replace_with_space = true)
    {
        if ($replace_with_space)
        {
            return preg_replace('!<[^>]*?>!', ' ', $string);
        }
        else
        {
            return strip_tags($string);
        }
    }
    
    public function NoSpam($email, $filterLevel = 'normal')
    {
        $email = strrev($email);
        $email = preg_replace('[\.]', '/', $email, 1);
        $email = preg_replace('[@]', '/', $email, 1);

        if ($filterLevel == 'low') {
            $email = strrev($email);
        }

        return $email;
    }
}