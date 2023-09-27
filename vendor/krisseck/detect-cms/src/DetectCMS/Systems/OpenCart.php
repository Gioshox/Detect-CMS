<?php

namespace DetectCMS\Systems;

class OpenCart extends \DetectCMS\DetectCMS
{

    public $methods = array(
        "generator_meta",
        "checkout_cart_in_html",
        "product_search_in_html"
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
     * Check meta tags for generator
     * @return [boolean]
     */
    public function generator_meta()
    {

        if ($this->home_html) {

            require_once(dirname(__FILE__) . "/../Thirdparty/simple_html_dom.php");

            if ($html = str_get_html($this->home_html)) {

                if ($meta = $html->find("meta[name='generator']", 0)) {

                    return strpos($meta->content, "OpenCart") !== FALSE;

                }

            }

        }

        return FALSE;

    }


    public function product_search_in_html()
    {
        if ($this->home_html) {
            if (preg_match("/index\.php\?route=product\/search/", $this->home_html)) {
                return true;
            }
        }

        return FALSE;
    }


    public function checkout_cart_in_html()
    {
        if ($this->home_html) {
            if (preg_match("/index\.php\?route=checkout\/cart/", $this->home_html)) {
                return true;
            }
        }

        return FALSE;
    }


}
