<?php
$_xhprof = [];

$_xhprof['dbtype'] = 'mysql'; // Only relevant for PDO
$_xhprof['dbhost'] = '127.0.0.1';
$_xhprof['dbuser'] = 'root';
$_xhprof['dbpass'] = '123456';
$_xhprof['dbname'] = 'xhprof';
$_xhprof['dbadapter'] = 'Pdo';
$_xhprof['servername'] = 'myserver';
$_xhprof['namespace'] = 'myapp';
$_xhprof['url'] = 'http:xhprof.fermin.com.cn';
$_xhprof['getparam'] = "_profile";

$_xhprof['serializer'] = 'php';

//These are good for Windows
/*
$_xhprof['dot_binary']  = 'C:\\Programme\\Graphviz\\bin\\dot.exe';
$_xhprof['dot_tempdir'] = 'C:\\WINDOWS\\Temp';
$_xhprof['dot_errfile'] = 'C:\\WINDOWS\\Temp\\xh_dot.err';
*/

//These are good for linux and its derivatives.

$_xhprof['dot_binary'] = '/usr/bin/dot';
$_xhprof['dot_tempdir'] = '/tmp';
$_xhprof['dot_errfile'] = '/tmp/xh_dot.err';


$ignoreURLs = [];
$ignoreDomains = [];
$exceptionURLs = [];
$exceptionPostURLs = [];
$exceptionPostURLs[] = "login";


$_xhprof['display'] = false;
$_xhprof['doprofile'] = false;

//Control IPs allow you to specify which IPs will be permitted to control when profiling is on or off within your application, and view the results via the UI.
$controlIPs = false; //Disables access controlls completely.
//$controlIPs = [];
//$controlIPs[] = "127.0.0.1";   // localhost, you'll want to add your own ip here
//$controlIPs[] = "::1";         // localhost IP v6

//$otherURLS = [];

// ignore builtin functions and call_user_func* during profiling
//$ignoredFunctions = ['call_user_func', 'call_user_func_array', 'socket_select'];

//Default weight - can be overidden by an Apache environment variable 'xhprof_weight' for domain-specific values
$weight = 100;

if ($domain_weight = getenv('xhprof_weight')) {
    $weight = $domain_weight;
}

unset($domain_weight);

/**
 * The goal of this function is to accept the URL for a resource, and return a "simplified" version
 * thereof. Similar URLs should become identical. Consider:
 * http://example.org/stories.php?id=2323
 * http://example.org/stories.php?id=2324
 * Under most setups these two URLs, while unique, will have an identical execution path, thus it's
 * worthwhile to consider them as identical. The script will store both the original URL and the
 * Simplified URL for display and comparison purposes. A good simplified URL would be:
 * http://example.org/stories.php?id=
 *
 * @param string $url The URL to be simplified
 * @return string The simplified URL
 */
function _urlSimilartor($url)
{
    //This is an example
    $url = preg_replace("!\d{4}!", "", $url);

    // For domain-specific configuration, you can use Apache setEnv xhprof_urlSimilartor_include [some_php_file]
    if ($similartorinclude = getenv('xhprof_urlSimilartor_include')) {
        require_once($similartorinclude);
    }

    $url = preg_replace("![?&]_profile=\d!", "", $url);
    return $url;
}

function _aggregateCalls($calls, $rules = null)
{
    $rules = [
        'Loading' => 'load::',
        'mysql' => 'mysql_'
    ];

    // For domain-specific configuration, you can use Apache setEnv xhprof_aggregateCalls_include [some_php_file]
    if (isset($run_details['aggregateCalls_include']) && strlen($run_details['aggregateCalls_include']) > 1) {
        require_once($run_details['aggregateCalls_include']);
    }

    $addIns = [];
    foreach ($calls as $index => $call) {
        foreach ($rules as $rule => $search) {
            if (strpos($call['fn'], $search) !== false) {
                if (isset($addIns[$search])) {
                    unset($call['fn']);
                    foreach ($call as $k => $v) {
                        $addIns[$search][$k] += $v;
                    }
                } else {
                    $call['fn'] = $rule;
                    $addIns[$search] = $call;
                }
                unset($calls[$index]);  //Remove it from the listing
                break;  //We don't need to run any more rules on this
            } else {
                //echo "nomatch for $search in {$call['fn']}<br />\n";
            }
        }
    }
    return array_merge($addIns, $calls);
}
