<?php

function str_ends_with($haystack, $needle) {
    return substr($haystack, strlen($haystack) - strlen($needle)) == $needle;
}

function formatMoney($amount, $currency) {
    if ($amount == null && $currency == null) {
        return '';
    }

    $decimals = ($currency == 'NOK') ? 2 : 6;
    return number_format(round($amount, $decimals), $decimals, ',', ' ') . ' ' .strtoupper($currency);
}

function getUrl($url, $usepost = false, $headers = array()) {
    $followredirect = false;

    echo '---------------------------------------------' . chr(10);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_USERAGENT, 'regnskap-php-html');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);  // man-in-the-middle defense by verifying ssl cert.
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);  // man-in-the-middle defense by verifying ssl cert.
    if ($followredirect) {
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    }
    if ($usepost) {
        echo '   POST ' . $url . chr(10);
        //$post_data = http_build_query($req, '', '&');
        curl_setopt($ch, CURLOPT_POST, true);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    }
    else {
        echo '   GET ' . $url . chr(10);
    }
    if (count($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    $res = curl_exec($ch);

    if ($res === false) {
        throw new Exception(curl_error($ch), curl_errno($ch));
    }

    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($res, 0, $header_size);
    $body = substr($res, $header_size);

    echo '   Response size: ' . strlen($body) . chr(10);

    //$info = curl_getinfo($ch);
    //var_dump($info);
    curl_close($ch);
    //echo file_get_contents($ckfile).chr(10);

    //echo '   strlen: '.strlen($res).chr(10);
    return array('headers' => $header, 'body' => $body);
}