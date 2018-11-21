<?php
/**
 * Created by PhpStorm.
 * User: matthewridderikhoff
 * Date: 2018-11-19
 * Time: 10:35 AM
 */

namespace App\Entities;


class FunctionLifespan
{
    public $name;
    public $start_line;
    public $end_line;

    public function __construct($name, $start_line, $end_line = null)
    {
        $this->name = $name;
        $this->start_line = $start_line;
        $this->end_line = $end_line;
    }

    public function setEndLine($end_line) {
        $this->end_line = $end_line;
    }

    public function hasEndLine() {
        return isset($this->end_line);
    }
}