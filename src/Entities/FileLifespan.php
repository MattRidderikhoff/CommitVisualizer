<?php
/**
 * Created by PhpStorm.
 * User: matthewridderikhoff
 * Date: 2018-11-18
 * Time: 6:45 PM
 */

namespace App\Entities;


class FileLifespan
{
    private $functions = [];
    private $file_name;
    private $file_size;

    public function __construct($file)
    {
        $this->file_name = $file['filename'];
        $this->file_size = $file['additions'];
        $this->addFile($file['patch']);
    }

    private function addFile($patch) {
        $lines = explode("\n", $patch);

        $current_line_num = 0;
        $current_line_num++; // 1st entry isn't a line in the file, it's the diff stats

        while ($current_line_num < count($lines)) {
            $current_line = $lines[$current_line_num];

            // create function object with line count and name, AND save it. return the new current_line_num
            if (strpos($current_line,' function ') !== false) {
                $current_line_num = $this->generateFunction($lines, $current_line_num);
            }

            $current_line_num++;
        }
    }

    private function generateFunction($lines, $current_index) {
        $current_line = $lines[$current_index];

        $function['name'] = $this->getFunctionName($current_line);
        $function['start_line'] = $current_index;

        $left_brace_count = 0;
        if (strpos($current_line, '{')) {

            if (strpos($current_line, '}')) {
                $function['end_line'] = $current_line; // 1 line function
            } else {
                $left_brace_count++;
            }
        }

        $function_end_line = null;
        while (!isset($function['end_line'])) {
            $current_index++;
            $current_line = $lines[$current_index];

            if (strpos($current_line, '{')) {
                $left_brace_count++;
            }

            if (strpos($current_line, '}')) {

                if ($left_brace_count == 1) {
                    $function['end_line'] = $current_index;
                } else {
                    $left_brace_count--;
                }
            }
        }

        $this->functions[] = $function;

        return $current_index;
    }

    private function getFunctionName($line) {
        $function_sub_line = explode('function ', $line);
        $function_sub_line = explode('(', $function_sub_line[1]);

        return array_shift($function_sub_line);
    }
}