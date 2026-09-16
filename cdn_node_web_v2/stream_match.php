<?php

// Panel types matching AdminBundle\Defines\PanelType
define('PANEL_XUIONE', 0);
define('PANEL_NXT', 2);
define('PANEL_XTREAMASTER', 3);
define('PANEL_STREAMCREED', 4);
define('PANEL_XTREAMUI', 5);
define('PANEL_XTREAMCODES', 6);

function stream_match_id($cfg, $uri)
{
    $panel = isset($cfg['panel_type']) ? intval($cfg['panel_type']) : -1;
    $pass = isset($cfg['live_streaming_pass']) ? (string) $cfg['live_streaming_pass'] : '';
    $path = parse_url($uri, PHP_URL_PATH);
    if( $path === null || $path === false ){
        $path = strtok($uri, '?');
    }
    $query = parse_url($uri, PHP_URL_QUERY);
    $q_token = '';
    // Do not use parse_str — it turns '+' into space and breaks base64 tokens
    if( $query && preg_match('/(?:^|&)token=([^&]*)/', $query, $m) ){
        $q_token = rawurldecode($m[1]);
    }

    switch( $panel ){
        case PANEL_XUIONE:
            return stream_match_xuione($path, $q_token, $pass);
        case PANEL_NXT:
            return stream_match_nxt($path, $pass);
        case PANEL_XTREAMASTER:
            return stream_match_xtreamaster($path, $q_token);
        case PANEL_STREAMCREED:
            return stream_match_streamcreed($path, $q_token);
        case PANEL_XTREAMUI:
        case PANEL_XTREAMCODES:
            if( $q_token !== '' ){
                if( $pass === '' ){
                    return null;
                }
                return stream_match_xtreamcodes($q_token, $pass);
            }
            return stream_match_from_url($path);
        default:
            return stream_match_from_url($path);
    }
}

function stream_match_from_url($path)
{
    if( preg_match('#(?:/live)?/[^/]+/[^/]+/(\d+)(?:_\d+)?(?:\.(?:ts|m3u8))?$#i', $path, $m) ){
        return $m[1];
    }
    if( preg_match('#/auth/(\d+)(?:_\d+)?\.(?:ts|m3u8)$#i', $path, $m) ){
        return $m[1];
    }
    return null;
}

function stream_match_nxt($path, $pass)
{
    if( $pass === '' ){
        return null;
    }
    if( !preg_match('#^/live/play/([^/]+)/(\d+)(?:_\d+)?(?:\.(?:ts|m3u8))?$#i', $path, $m) ){
        return null;
    }
    $token = rawurldecode($m[1]);
    if( stream_decrypt_nxt($token, $pass) === null ){
        return null;
    }
    return $m[2];
}

function stream_decrypt_nxt($token, $pass)
{
    $secret = md5($pass);
    $key = hash('sha256', $secret);
    $iv = '2e065a796a8e527d';
    $inner = base64_decode($token, true);
    if( $inner === false || $inner === '' ){
        return null;
    }
    $cipher = base64_decode($inner, true);
    if( $cipher === false || $cipher === '' ){
        return null;
    }
    $mid = openssl_decrypt($cipher, 'AES-256-CBC', $key, 0, $iv);
    if( $mid === false || $mid === '' ){
        return null;
    }
    $activity_id = base64_decode($mid, true);
    if( $activity_id === false || $activity_id === '' ){
        return null;
    }
    if( !preg_match('#^[A-Za-z0-9_\-]+$#', $activity_id) ){
        return null;
    }
    return $activity_id;
}

function stream_match_xuione($path, $q_token, $pass)
{
    if( $pass === '' ){
        return null;
    }
    $tokens = array();
    if( $q_token !== '' ){
        $tokens[] = $q_token;
    }
    if( preg_match('#^/auth/([^/?]+)$#i', $path, $m) && !preg_match('#^\d+\.\w+$#', $m[1]) ){
        $tokens[] = rawurldecode($m[1]);
    }
    foreach( array_unique($tokens) as $token ){
        $plain = stream_decrypt_xuione($token, $pass);
        if( $plain === null ){
            continue;
        }
        $data = json_decode($plain, true);
        if( is_array($data) && isset($data['stream_id']) ){
            return (string) $data['stream_id'];
        }
    }
    return null;
}

function stream_decrypt_xuione($token, $pass)
{
    $openssl_extra = 'fNiu3XD448xTDa27xoY4';
    $b64 = strtr($token, '-_', '+/');
    $pad = strlen($b64) % 4;
    if( $pad ){
        $b64 .= str_repeat('=', 4 - $pad);
    }
    $raw = base64_decode($b64, true);
    if( $raw === false || $raw === '' ){
        return null;
    }
    $aes_key = md5(sha1($openssl_extra) . $pass);
    $iv = substr(md5(sha1($pass)), 0, 16);
    $plain = openssl_decrypt($raw, 'aes-256-cbc', $aes_key, OPENSSL_RAW_DATA, $iv);
    if( $plain === false || $plain === '' ){
        return null;
    }
    return $plain;
}

function stream_match_xtreamcodes($token, $pass)
{
    $b64 = strtr($token, '-_', '+/');
    $pad = strlen($b64) % 4;
    if( $pad ){
        $b64 .= str_repeat('=', 4 - $pad);
    }
    $raw = base64_decode($b64, true);
    if( $raw === false || $raw === '' ){
        return null;
    }
    $keys = array(md5($pass));
    if( preg_match('/^[0-9a-f]{32}$/i', $pass) ){
        $keys[] = strtolower($pass);
    }
    foreach( $keys as $key ){
        $out = '';
        $klen = strlen($key);
        for( $i = 0; $i < strlen($raw); $i++ ){
            $out .= chr(ord($raw[$i]) ^ ord($key[$i % $klen]));
        }
        $data = json_decode($out, true);
        if( is_array($data) && isset($data['stream_id']) ){
            return (string) $data['stream_id'];
        }
    }
    return null;
}

function stream_match_streamcreed($path, $q_token)
{
    if( $q_token === '' || strlen($q_token) !== 20 ){
        return null;
    }
    return stream_match_from_url($path);
}

function stream_match_xtreamaster($path, $q_token)
{
    if( $q_token !== '' && strlen($q_token) === 20 ){
        return stream_match_from_url($path);
    }
    if( !preg_match('#^/([A-Za-z0-9+/=_-]{40,})$#', $path, $m) ){
        return null;
    }
    $plain = stream_decrypt_xtreamaster($m[1]);
    if( $plain === null ){
        return null;
    }
    if( preg_match('#^live/play/(.+)$#', $plain, $tm) ){
        $inner = stream_decrypt_xtreamaster($tm[1]);
        if( $inner === null ){
            return null;
        }
        $data = json_decode($inner, true);
        if( is_array($data) ){
            if( isset($data['id']) ){
                return (string) $data['id'];
            }
            if( isset($data['stream_id']) ){
                return (string) $data['stream_id'];
            }
        }
    }
    return null;
}

function stream_decrypt_xtreamaster($in)
{
    $method = 'AES-128-CBC';
    $key = base64_decode('a3UpaHc2QDc4NSg2NzUmNGZuZmchbmZyaGokZm4');
    $iv = base64_decode('ZEAjXiZ4eComKiUkIyVeJg');
    $b64 = strtr($in, '-_', '+/');
    $pad = strlen($b64) % 4;
    if( $pad ){
        $b64 .= str_repeat('=', 4 - $pad);
    }
    $raw = base64_decode($b64, true);
    if( $raw === false || $raw === '' ){
        return null;
    }
    $plain = openssl_decrypt($raw, $method, $key, OPENSSL_DONT_ZERO_PAD_KEY, $iv);
    if( $plain === false || $plain === '' ){
        return null;
    }
    return $plain;
}
