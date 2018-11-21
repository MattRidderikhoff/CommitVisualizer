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
    private $start_line;
    private $end_line;
    private $commit_date;

    public function __construct($name, $commit_date, $start_line, $end_line = null)
    {
        $this->name = $name;
        $this->start_line = $start_line;
        $this->commit_date = $commit_date;
        $this->end_line = $end_line;
    }

    public function setEndLine($end_line) {
        $this->end_line = $end_line;
    }

    public function hasEndLine() {
        return isset($this->end_line);
    }

    public function getCommitDate() {
        return $this->commit_date;
    }
}