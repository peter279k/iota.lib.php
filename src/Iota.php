<?php

namespace Iota;

class Iota
{
    private $settings;

    private $utils;

    private $valid;

    private $multisig;

    public function __construct(array $settings = [])
    {
        $this->settings = $settings;
    }

    public function changeNode(array $settings = [])
    {
        $this->setSettings($settings);
    }

    public function setSettings(array $settings = [])
    {
        $settings = $settings ?? [];
        $settings['version'] = $settings['version'] ?? '1.0.0';
        $settings['host'] = $settings['host'] ?? 'http://localhost';
        $settings['port'] = $settings['port'] ?? 14265;
        $settings['provider'] = $settings['provider'] || str_replace(['/', PHP_EOL], '', $settings['host']) . ":" . $settings['port'];
        $settings['sandbox'] = $settings['sandbox'] ?? false;
        $settings['token'] = $settings['token'] ?? false;
        $settings['username'] = $settings['username'] ?? false;
        $settings['password'] = $settings['password'] ?? false;

        if ($settings['sandbox']) {
            // remove backslash character
            $settings['sandbox'] = str_replace(['/', PHP_EOL], '', $settings['provider']);
            $settings['provider'] = $settings['sandbox'] . '/commands';
        }

        // Using Guzzle?
        $makeRequest = new MakeRequest($settings['provider'], $settings['token'] ?? $settings['username'], $settings['password']);
        $settings['api'] = new Api($makeRequest, $settings['sandbox']);

        $this->utils = new Utils();
        $this->valid = new InputValidator();
        $this->multisig = new Multisig($makeRequest);
    }

    public function getSettings()
    {
        return $this->settings;
    }

    public function getUtils()
    {
        return $this->utils;
    }

    public function getValid()
    {
        return $this->valid;
    }

    public function getMultisig()
    {
        return $this->multisig;
    }
}
