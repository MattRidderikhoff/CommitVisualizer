<?php
/**
 * Created by PhpStorm.
 * User: matthewridderikhoff
 * Date: 2018-11-18
 * Time: 6:33 PM
 */

namespace App\Entities;


class CommitHistory
{
    private $files = [];

    public function addFile($file) {
        $this->files[] = $file;
    }
}