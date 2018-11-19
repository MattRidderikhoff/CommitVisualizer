<?php
/**
 * Created by PhpStorm.
 * User: matthewridderikhoff
 * Date: 2018-11-19
 * Time: 2:25 PM
 */

namespace App\Entities;


class RepoOverview
{
    private $files = [];

    public function addFile($file) {
        $file_name = $file->getName();

        if (!isset($this->files[$file_name])) {
            $this->files[$file_name];
        }
    }

    public function hasFile($file_name) {

        return isset($this->files[$file_name]);
    }
}