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
        $this->getFunctions($file['patch']);
    }

    private function getFunctions($patch) {
        $lines_string = explode('@@', $patch)[2];
        $lines = explode('+', $lines_string);
        $removed = array_shift($lines); // remove blank entry created by explode()

        $current_line = 0;
        while (count($lines) > 0) {
            $current_line = array_shift($lines);
            $current_line++;

            if (strpos($current_line,' function ') !== false) {
                $function_start_line = null;
                $function_end_line = null;
                $function_name = null;


                // get function name from start line
                $function_name = $current_line;

                // now make sure we can find the end of the function, which is where we have a matching "}" for our starting "{"
                if (strpos($current_line, '{') !== false) {
                    $function_start_line = $current_line;
                }
                
                // iterate through the remaining lines
                while (!isset($function_end_line)) {
                    $left_brace_count = (isset($function_start_line)) ? 1 : 0;
                }

                // wait until we get to the end of the function


            }
        }
    }
}