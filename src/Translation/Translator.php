<?php

namespace Gecche\Cupparis\Translation;

use Illuminate\Support\Facades\Config;

class Translator extends \Illuminate\Translation\Translator {

    /**
     * Make the place-holder replacements on a line.
     *
     * @param  string  $line
     * @param  array   $replace
     * @return string
     */
    
    /*
    protected function makeReplacements($line, array $replace) {
        $replace = $this->sortReplacements($replace);

        foreach ($replace as $key => $value) {
            //$line = str_replace(':' . $key, $value, $line);
            $line = str_replace(':' . $key, $this->get($value), $line);
        }

        return $line;
    }
     * 
     */

    /**
     * Get the translation for the given key with "Magic" Cupparis works! :D
     *
     * @param  string  $key
     * @param  array   $replace
     * @param  string  $locale
     * @param  array   $magic params
     * @return string
     */
    public function getM($key, array $replace = array(), $locale = null, $magic_params = array(), $separator = '_') {



        $line = $this->getRaw($key, $replace, $locale);
        // If the line doesn't exist, we will return back the key which was requested as
        // that will be quick to spot in the UI if language keys are wrong or missing
        // from the application's language files. Otherwise we can return the line.
        //performs magic
        if ($line === null) {

            list($finalKey, $prefixKey) = $this->splitFinalKey($key);

            $suffix = array_get($magic_params, 'suffix', false);
            $prefix = array_get($magic_params, 'prefix', false);
            $prefixed = false;
            $suffixed = false;

            if ($prefix && starts_with($finalKey, $prefix . $separator)) {
                $prefix = $prefix . $separator;
                $keyWithoutPrefix = $prefixKey . substr($finalKey, strlen($prefix));
                $line = $this->getRaw($keyWithoutPrefix);
                $prefixed = true;
            }

            if ($line === null && $suffix && ends_with($key, $separator . $suffix)) {
                $suffix = $separator . $suffix;
                $line = $this->getRaw(substr($key, 0, -strlen($suffix)));
                $suffixed = true;
            }

            if ($line === null && $prefixed && $suffixed) {
                $lastKey = substr($keyWithoutPrefix, 0, -strlen($suffix));
                $line = $this->getRaw($lastKey);
            }
        }

        if (is_null($line) && array_get($magic_params, 'nullable', false)) {
            return null;
        }

        if (is_null($line)) {
            $line = $key;
            if (array_get($magic_params, 'humanize', true)) {
                $line = $this->humanize($line);
            }
        }



        //A bit of magic more! :D
//        if (array_get($magic_params, 'humanize', true)) {
//            $line = $this->humanize($line);
//        }

        $capitals = array_get($magic_params, 'capitals', false);
        switch ($capitals) {
            case 'ucfirst':
                $line = ucfirst($line);
                break;
            case 'capitalize':
                $line = ucwords($line);
                break;
            case 'uppercase':
                $line = strtoupper($line);
                break;
            default:
                break;
        }

        return $line;
    }

    /*
     * As get but returning null if the $key does not exists
     */

    public function getRaw($key, array $replace = array(), $locale = null, $fallback = true) {
        $line = null;
        list($namespace, $group, $item) = $this->parseKey($key);

        // Here we will get the locale that should be used for the language line. If one
        // was not passed, we will use the default locales which was given to us when
        // the translator was instantiated. Then, we can load the lines and return.
        $locales = $fallback ? $this->localeArray($locale)
            : [$locale ?: $this->locale];

        foreach ($locales as $locale) {
            $this->load($namespace, $group, $locale);

            $line = $this->getLine(
                    $namespace, $group, $locale, $item, $replace
            );

            if (!is_null($line))
                break;
        }
        return $line;
    }

    public function humanize($key) {
        $key = str_replace(array('-', '_'), ' ', $key);
        return $key;
    }

    protected function splitFinalKey($key) {
        $lastDot = strrpos($key, '.');
        if ($lastDot === false) {
            return array($key, '');
        }
        return array(substr($key, $lastDot + 1), substr($key, 0, $lastDot + 1));
    }

    protected function splitPrefix($key, $separator) {
        $separatorPos = strpos($key, $separator);
        if ($separatorPos === false) {
            return array(false, $key);
        }
        return array(substr($key, 0, $separatorPos), substr($key, $separatorPos + 1));
    }

    protected function splitSuffix($key, $separator) {
        $separatorPos = strrpos($key, $separator);
        if ($separatorPos === false) {
            return array(false, $key);
        }
        return array(substr($key, $separatorPos), substr($key, 0, $separatorPos + 1));
    }

    public function getMFormField($key, $model, array $replace = array(), $locale = null, $capitals = 'ucfirst', $separator = '_', $path = 'fields.') {
        $first_attempt_key = $path . $model . $separator . $key;
        $result = $this->getM($first_attempt_key, $replace, $locale, array('prefix' => $model, 'capitals' => $capitals, 'nullable' => true), $separator);
        if ($result === null && ends_with($key, '_id')) {
            $second_attempt_key = $path . $model . $separator . substr($key, 0, -3);
            $result = $this->getM($second_attempt_key, $replace, $locale, array('prefix' => $model, 'capitals' => $capitals), $separator);
        }
        if ($result === null && $this->checkLang($key)) {
            $third_attempt_key = $path . $model . $separator . substr($key, 0, -3);
            $result = $this->getM($third_attempt_key, $replace, $locale, array('prefix' => $model, 'capitals' => $capitals), $separator);
        }
        return $result;
    }

    public function getMFormLabel($key, $model, array $replace = array(), $locale = null, $capitals = 'ucfirst', $separator = '_', $path = 'fields.') {

        $first_attempt_key = $path . $model . $separator . $key . $separator . 'label';
        $result = $this->getM($first_attempt_key, $replace, $locale, array('prefix' => $model, 'suffix' => 'label', 'capitals' => $capitals, 'nullable' => true), $separator);
        if ($result === null && ends_with($key, '_id')) {
            $second_attempt_key = $path . $model . $separator . substr($key, 0, -3) . $separator . 'label';
            $result = $this->getM($second_attempt_key, $replace, $locale, array('prefix' => $model, 'suffix' => 'label', 'capitals' => $capitals), $separator);
        }
        if ($result === null && $this->checkLang($key)) {
            $third_attempt_key = $path . $model . $separator . substr($key, 0, -3);
            $result = $this->getM($third_attempt_key, $replace, $locale, array('prefix' => $model, 'capitals' => $capitals), $separator);
        }
        return $result;
    }

    public function getMFormMsg($key, $model, array $replace = array(), $locale = null, $capitals = 'ucfirst', $separator = '_', $path = 'fields.') {
        $first_attempt_key = $path . $model . $separator . $key . $separator . 'msg';
        return $this->getM($first_attempt_key, $replace, $locale, array('prefix' => $model, 'capitals' => $capitals, 'nullable' => true), $separator);
    }

    public function getMFormAddedLabel($key, $model, array $replace = array(), $locale = null, $capitals = 'ucfirst', $separator = '_', $path = 'fields.') {
        $first_attempt_key = $path . $model . $separator . $key . $separator . 'addedLabel';
        return $this->getM($first_attempt_key, $replace, $locale, array('prefix' => $model, 'capitals' => $capitals, 'nullable' => true), $separator);
    }
    
    public function checkLang($key) {
        if (strlen($key) <= 3 || substr($key,-3,1) !== '_') {
            return false;
        }
        $key = substr($key,-2);
        $langs = Config::get('app.langs');
        if (in_array($key,$langs)) {
            return true;
        }
        return false;
    }

}
