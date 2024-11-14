<?php

use RestrictUserAccess\Application;

if (!function_exists('rua')) {
    /**
     * @return Application
     */
    function rua()
    {
        return Application::instance();
    }
}