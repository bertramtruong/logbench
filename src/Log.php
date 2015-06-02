<?php

namespace BertramTruong\LogBench;

use Exception;
use Faker\Factory;

abstract class Log
{

    const DELIMITER_START = "{%";
    const DELIMITER_END = "%}";
    const DELIMITER_SIZE = 2;

    private $logFormat;
    private $faker;

    function __construct($logFormat)
    {
        $this->logFormat = $logFormat;
        $this->faker = Factory::create('en_AU');
    }

    private function expandRandom($variable)
    {
        // does it have arguments
        $arguments = explode(",", $variable);
        $variable = $arguments[0];
        switch ($variable) {
            case 'NUMBER':
                $min = (int)$arguments[1];
                $max = (int)$arguments[2];
                return $this->faker->numberBetween($min, $max);
            case 'USER':
                $user = $this->faker->userName;
                if (strpos($user, ".") !== FALSE) {
                    $x = explode(".", $user);
                    return $x[0];
                }
                return $user;
            case 'MONTH':
                return $this->faker->dateTimeThisYear->format('M');
            case 'DATETIME':
                return $this->faker->dateTimeThisYear->format($arguments[1]);
            case 'PROGRAM':
                return $this->faker->word;
            case 'MESSAGE':
                return $this->faker->sentence();
            default:
                throw new Exception("Unknown random expansion: {$variable}");
        }
    }

    /**
     * Generate a single log matching the format.
     */
    public function generate()
    {
        $string = $this->logFormat;
        while (strpos($string, self::DELIMITER_START) !== FALSE) {
            $startIdx = strpos($string, self::DELIMITER_START) + self::DELIMITER_SIZE;
            $endIdx = strpos($string, self::DELIMITER_END, $startIdx);
            $variable = substr($string, $startIdx, ($endIdx - $startIdx));

            $random = $this->expandRandom($variable);
            $string = str_replace("{%{$variable}%}", $random, $string);
        }
        return $string;
    }

}