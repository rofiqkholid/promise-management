<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function encryptId($id)
    {
        $s1 = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
        $s2 = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
        $mid = str_pad($id, 6, '0', STR_PAD_LEFT);
        $plain = $s1 . $mid . $s2;
        
        $appKey = config('app.key', 'mng_secret_fallback_key_123');
        $key = substr(md5($appKey), 0, 16);
        
        $encrypted = '';
        for ($i = 0; $i < 16; $i++) {
            $encrypted .= chr(ord($plain[$i]) ^ ord($key[$i]));
        }
        
        return bin2hex($encrypted);
    }

    protected function decryptId($hexString)
    {
        try {
            if (is_numeric($hexString) && strlen((string)$hexString) < 12) {
                return (int)$hexString;
            }
            
            $hex = str_replace('-', '', $hexString);
            $encrypted = null;

            if (strlen($hex) === 32) {
                $encrypted = @hex2bin($hex);
            } else {
                // Fallback for previous Base64 URL-safe format
                $base64 = strtr($hexString, '-_', '+/');
                $len = strlen($base64) % 4;
                if ($len) {
                    $base64 .= str_repeat('=', 4 - $len);
                }
                $decoded = base64_decode($base64);
                if ($decoded !== false && strlen($decoded) === 16) {
                    $encrypted = $decoded;
                }
            }

            if (!$encrypted || strlen($encrypted) !== 16) {
                return null;
            }
            
            $appKey = config('app.key', 'mng_secret_fallback_key_123');
            $key = substr(md5($appKey), 0, 16);
            
            $plain = '';
            for ($i = 0; $i < 16; $i++) {
                $plain .= chr(ord($encrypted[$i]) ^ ord($key[$i]));
            }
            
            $s1 = substr($plain, 0, 5);
            $mid = substr($plain, 5, 6);
            $s2 = substr($plain, 11, 5);
            
            if (is_numeric($s1) && is_numeric($mid) && is_numeric($s2)) {
                return (int)$mid;
            }
        } catch (\Exception $e) {
            return null;
        }
        return null;
    }
}
