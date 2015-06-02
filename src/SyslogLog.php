<?php

namespace BertramTruong\LogBench;

class SyslogLog extends Log
{

    function __construct()
    {
        parent::__construct("{%DATETIME,M d H:i:s%} {%USER%} {%PROGRAM%}[{%NUMBER,100,1000%}]: {%MESSAGE%}\r\n");
    }

}