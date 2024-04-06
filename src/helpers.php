<?php

use RestrictUserAccess\Application;

if (!function_exists('rua_app')) {
    /**
     * @param $id
     * @return mixed|Application
     * @throws Exception
     */
    function rua_app($id = null)
    {
        if ($id === null) {
            return Application::instance();
        }
        return Application::instance()->get($id);
    }
}
