<?php
/**
 * Created by PhpStorm.
 * User: matthewridderikhoff
 * Date: 2018-11-21
 * Time: 11:30 AM
 */

namespace App\Entities;


class FunctionState
{
    private $name;
    private $commit_date;
    private $lines;

    private $start_line_num;
    private $end_line_num;

    public function __construct($name, $commit_date, $lines, $start_line_num)
    {
        $this->name = $name;
        $this->commit_date = $commit_date;
        $this->lines = $lines;

        $this->start_line_num = $start_line_num;
        $this->end_line_num = $start_line_num + count($this->lines) - 1;
    }

    public function addLine($line, $line_index) {
        array_splice( $this->lines, $line_index-1, 0, $line);
        $this->end_line_num++;
    }

    public function removeLine($line_index) {
        unset($this->lines[$line_index]);
        $this->end_line_num--;
    }

    public function setCommitDate($commit_date) {
        $this->commit_date = $commit_date;
    }

    public function getCommitDate() {
        return $this->commit_date;
    }

    public function getLines() {
        return $this->lines;
    }

    public function getStartLineNum() {
        return $this->start_line_num;
    }

    public function getEndLineNum() {
        return $this->end_line_num;
    }
}