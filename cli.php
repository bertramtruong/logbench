#!/usr/bin/php
<?php

use BertramTruong\LogBench\SyslogLog;

require_once 'vendor/autoload.php';
ini_set('date.timezone', 'Australia/Melbourne');

$nLogs = 100;
if ($argc != 1 && $argc != 2) {
    die ("Usage: cli.php <number of logs/config file>" . PHP_EOL);
}

$config = [];
$continuous = false;
if (strpos($argv[1], ".json") !== FALSE) {
    // load config
    $configPath = realpath($argv[1]);
    if ($configPath === FALSE) {
        die("Could not load config file!");
    }
    $configData = file_get_contents($configPath);
    $config = json_decode($configData, true);
    $nLogs = ((int)$config['logs']);
    $continuous = (isset($config['continuous']) ? (bool)$config['continuous'] : false);
} else {
    $nLogs = (int) $argv[1];
    if ($nLogs <= 0) {
        die ("Usage: cli.php <number of logs/config file>" . PHP_EOL);
    }
}

// holder for logs
$logs = [];

$syslog = new SyslogLog();
for ($i = 0; $i < $nLogs; $i++) {
    if ($i % 500 == 0) {
        $n = $i + 1;
        echo "{$n} logs generated ..." . PHP_EOL;
    }
    $logs[] = $syslog->generate();
}

if (empty($config)) {
    $handle = fopen("php://stdin", "r");
    // ready
    echo "==========" . PHP_EOL;
    echo sizeof($logs) . " logs are ready to be sent." . PHP_EOL;
    echo "==========" . PHP_EOL;
    echo "Type 'yes' to continue: ";
    $line = fgets($handle);
    if (trim($line) != 'yes') {
        echo "ABORTING!\n";
        exit;
    }

    echo PHP_EOL;
    echo PHP_EOL;
    echo PHP_EOL;
    echo "Server hostname/IP address: ";
    $line = fgets($handle);
    $server = trim($line);

    echo "TCP/UDP: ";
    $line = fgets($handle);
    $protocol = strtolower(trim($line));

    echo "Port number: ";
    $line = fgets($handle);
    $port = (int)trim($line);

    // sanity check
    if ($server == "" || $port <= 0) {
        die("Invalid server and/or port number specified!");
    }
} else {
    $server = $config['server'];
    $protocol = strtolower($config['protocol']);
    $port = (int)$config['port'];
}

echo "Sending logs to {$server}:{$port} ..." . PHP_EOL;
switch ($protocol) {
    case 'tcp':
        $socket = socket_create(AF_INET, SOCK_STREAM, 0);
        if (!socket_connect($socket, $server, $port)) {
            die("Could not connect to {$server}:{$port}" . PHP_EOL);
        }
        do {
            foreach ($logs as $log) {
                socket_send($socket, $log, strlen($log), 0);
            }
        } while ($continuous);
        socket_close($socket);
        break;
    case 'udp':
        $socket = stream_socket_client("udp://{$server}:{$port}", $errno, $errstr);
        if ($socket) {
            do {
                foreach ($logs as $log) {
                    fwrite($socket, $log);
                }
                fclose($socket);
            } while ($continuous);
        }
        break;
    default:
        die("Invalid protocol specified!");
}

echo "DONE!" . PHP_EOL;