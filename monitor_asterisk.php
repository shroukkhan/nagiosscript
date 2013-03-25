<?php
/**
 * Created by JetBrains PhpStorm.
 * User: fingidemo
 * Date: 3/25/13
 * Time: 12:24 PM
 * To change this template use File | Settings | File Templates.
 */

require_once(getcwd().'/sshlib/NET/SSH2.php');


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
if(!$ssh->login($login,$password))
{
    exitCritical("failed to login to $server");
}

call_user_func($function);

function check_extension()
{
    global $argv,$ssh;
    $extension = trim($argv[5]);
    if(empty($extension))
        exitCritical("no extension number is provided..");

    $command = "asterisk -rx \"sip show peer " . $extension . "\" | grep \"Status\"";
    $result = $ssh->exec($command);
    if (strpos($result, "OK") === false) {
        $msg= "Current Status:->" . $result." | extension $extension is NOT registered!" ;
        exitCritical($msg);
    }
    else {
        $msg = "Current Status:->" . $result ;
        exitOk($msg);
    }
}

function check_trunk()
{
    global $argv,$ssh;
    $trunk = trim($argv[5]);
    if(empty($trunk))
        exitCritical("no trunks number is provided..");

    $command = "asterisk -rx \"sip show registry \" | grep $trunk";
    $result = $ssh->exec($command);
    if (strpos($result, "Registered") === false) {
        $msg= "Current Status:->" . $result." | trunk $trunk is NOT registered!" ;
        exitCritical($msg);
    }
    else {
        $msg = "Current Status:->" . $result ;
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