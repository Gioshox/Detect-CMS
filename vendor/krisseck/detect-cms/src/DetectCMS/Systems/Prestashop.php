<?php

namespace DetectCMS\Systems;

class Prestashop extends \DetectCMS\DetectCMS
{

    public $methods = array(
        "generator_header",
        "generator_meta",
        "script_var"
    );

    public $home_html = "";
    public $home_headers = array();
    public $url = "";

    function __construct($home_html, $home_headers, $url)
    {
        $this->home_html = $home_html;
        $this->home_headers = $home_headers;
        $this->url = $url;
    }

    /**
     * Check for Generator header
     * @return [boolean]
     */
    public function generator_header()
    {

        if (is_array($this->home_headers)) {

            foreach ($this->home_headers as $key => $line) {
                if ($key == 'set-cookie') {
                    if (preg_match("/PrestaShop-/", $line)) {
                        return true;
                    }

                }

            }

        }

        return FALSE;

    }

    /**
     * Check meta tags for generator
     * @return [boolean]
     */
    public function generator_meta()
    {

        if ($this->home_html) {

            require_once(dirname(__FILE__) . "/../Thirdparty/simple_html_dom.php");

            if ($html = str_get_html($this->home_html)) {

                if ($meta = $html->find("meta[name='generator']", 0)) {

                    return strpos($meta->content, "PrestaShop") !== FALSE;

                }

            }

        }

        return FALSE;

    }


    public function script_var()
    {
        if ($this->home_html) {
            if (preg_match("/var prestashop = /", $this->home_html)) {
                return true;
            }
        }

        return FALSE;
    }


}
