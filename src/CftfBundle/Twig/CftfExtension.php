<?php

namespace CftfBundle\Twig;

class CftfExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('sanitize', array($this, 'sanitize')),
        );
    }

    public function sanitize($string)
    {
        $string = filter_var($string, FILTER_SANITIZE_STRING);

        return $string;
    }
}