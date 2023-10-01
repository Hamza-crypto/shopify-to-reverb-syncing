<?php

if (! function_exists('convertResponseToString')) {
    function convertResponseToString($response)
    {
        $result = '';
        foreach ($response as $key => $value) {
            $result .= $key.': '.(is_array($value) ? json_encode($value) : $value).', ';
        }

        $result = rtrim($result, ', ');
        $result = substr($result, 0, 2000);

        return $result;
    }
}
