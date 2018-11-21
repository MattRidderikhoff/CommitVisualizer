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
    private $creation_date;

    public function __construct($file, $commit_date)
    {
        $this->file_name = $file['filename'];
        $this->file_size = $file['additions'];
        $this->creation_date = $commit_date;
        $this->addFile($file['patch'], $commit_date);
    }

    public function modify($file) {
        $lines = explode("\n", $file['patch']);

        $additions = $file['additions'];
        $deletions = $file['deletions'];

    }

    private function addFile($patch, $commit_date) {
        $lines = explode("\n", $patch);

        $current_line_num = 0;
        $current_line_num++; // 1st entry isn't a line in the file, it's the diff stats

        while ($current_line_num < count($lines)) {
            $current_line = $lines[$current_line_num];

            // create function object with line count and name, AND save it. return the new current_line_num
            if (strpos($current_line,' function ') !== false) {

                if (strpos($current_line, ';') === false) { // todo: ignoring abstract/unimplemented functions for now
                    $current_line_num = $this->generateFunction($lines, $current_line_num, $commit_date);
                }
            }

            $current_line_num++;
        }
    }

    private function generateFunction($lines, $current_index, $commit_date) {
        $current_line = $lines[$current_index];

        $function_commit = new FunctionState($this->generateFunctionName($current_line), $commit_date, $current_index);

        $left_brace_count = 0;
        if (strpos($current_line, '{') !== false) {

            if (strpos($current_line, '}') !== false) {
                $function_commit->setEndLine($current_line); // 1 line function
            } else {
                $left_brace_count++;
            }
        }

        while (!$function_commit->hasEndLine()) {
            $current_index++;
            $current_line = $lines[$current_index];

            if (strpos($current_line, '{') !== false) {
                $left_brace_count++;
            }

            if (strpos($current_line, '}') !== false) {

                if ($left_brace_count == 1) {
                    $function_commit->setEndLine($current_index);
                } else {
                    $left_brace_count--;
                }
            }
        }

        $this->functions[] = new FunctionLifespan($function_commit);

        return $current_index;
    }

    private function generateFunctionName($line) {
        $function_sub_line = explode('function ', $line);
        $function_sub_line = explode('(', $function_sub_line[1]);

        return array_shift($function_sub_line);
    }

    public function getName() {
        return $this->file_name;
    }
}