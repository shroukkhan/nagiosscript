<?php
/**
 * Created by JetBrains PhpStorm.
 * User: fingidemo
 * Date: 3/25/13
 * Time: 12:24 PM
 * To change this template use File | Settings | File Templates.
 */

error_reporting(0); // disable all error reporting
require_once(dirname(__FILE__) . '/sshlib/Net/SSH2.php');


$thisFile = trim($argv[0]);

$server = trim($argv[1]);
$login = trim($argv[2]);
$password = trim($argv[3]);

$function = trim($argv[4]);


$STA_OK = 0;
$STA_WARNING = 1;
$STA_CRITICAL = 2;
$STA_UNKNOWN = 3;


$ssh = new Net_SSH2($server);
// login to server...
if (!$ssh->login($login, $password)) {
    exitCritical("failed to login to $server");
}

call_user_func($function);


function check_multiple_extensions()
{
    global $argv, $ssh, $argc;
    $extensions = array();
    for ($i = 5; $i < $argc; $i++) {
        $ex = trim($argv[$i]);
        if (!empty($ex))
            $extensions[] = $ex;
    }

    if (empty($extensions))
        exitCritical("no extension number is provided..");

    foreach ($extensions as &$e)
        $e = "^$e"; // add ^ to denote it will be the beginingg of the line

    $extensions = implode("\\|", $extensions);

    $command = "asterisk -rx \"sip show peers\" | grep -w \"$extensions\"";


    $result = $ssh->exec($command);
    $result = explode("\n", $result);

    $notOkArray = array();
    $okArray = array();
    foreach ($result as $r) {

        $line = trim($r);
        if (empty($line))
            continue;

        preg_match('/^(\w+)/', $line, $extensionNumber);
        $extensionNumber = $extensionNumber[0];
        if ($extensionNumber == null) {
            exitCritical("could not extract extension number from $line ! call khan..");
        }

        preg_match('/OK \\(\\d{1,5} ms\\)/', $line, $Ok);
        $Ok = $Ok[0];
        $isRegisterd = $Ok === null ? false : true;

        if (!$isRegisterd)
            $notOkArray[] = $extensionNumber;
        else
            $okArray[] = $extensionNumber;
    }

    if (!empty($notOkArray)) {
        $notRegisteredExtensions = implode(" , ", $notOkArray);
        exitCritical("Following extensions not registered: $notRegisteredExtensions");
    } else
        exitOk("All extensions registered in this group");


}

function check_extension()
{
    global $argv, $ssh;
    $extension = trim($argv[5]);
    if (empty($extension))
        exitCritical("no extension number is provided..");

    $command = "asterisk -rx \"sip show peer " . $extension . "\" | grep \"Status\"";
    $result = $ssh->exec($command);
    if (strpos($result, "OK") === false) {
        $msg = "Current Status:->" . $result . " | extension $extension is NOT registered!";
        exitCritical($msg);
    } else {
        $msg = "Current Status:->" . $result;
        exitOk($msg);
    }
}

function check_trunk()
{
    global $argv, $ssh;
    $trunk = trim($argv[5]);
    if (empty($trunk))
        exitCritical("no trunks number is provided..");

    $command = "asterisk -rx \"sip show registry \" | grep $trunk";
    $result = $ssh->exec($command);
    if (strpos($result, "Registered") === false) {
        $msg = "Current Status:->" . $result . " | trunk $trunk is NOT registered!";
        exitCritical($msg);
    } else {
        $msg = "Current Status:->" . $result;
        exitOk($msg);
    }
}


/* -------------------------------------------------------------------------------- */

function exitCritical($msg)
{
    global $STA_CRITICAL;
    echo $msg;
    exit($STA_CRITICAL);
}

function exitOk($msg)
{
    global $STA_OK;
    echo $msg;
    exit($STA_OK);
}

function exitWarning($msg)
{
    global $STA_WARNING;
    echo $msg;
    exit($STA_WARNING);
}

function exitUnknown($msg)
{
    global $STA_UNKNOWN;
    echo $msg;
    exit($STA_UNKNOWN);
}