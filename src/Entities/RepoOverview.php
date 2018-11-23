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
            $this->files[$file_name] = $file;
        }
    }

    public function hasFile($file_name) {

        return isset($this->files[$file_name]);
    }

    public function modifyFile($file, $commit_date) {
        $file_lifespan = $this->files[$file['filename']];
        $file_lifespan->modify($file, $commit_date);
    }

  /**
   * @return array of files
   */
  public function getFiles(): array
  {
    return $this->files;
  }

  public function getCommitDates(): array
  {
    $dates = [];
    foreach ($this->files as $file) {
      foreach ($file->getFunctions() as $func){
        foreach ($func->getCommits() as $commit){
            $date = $commit->getCommitDate();
            array_push($dates, $date->format('Y-m-d'));
        }
      }
    }
    $datesUnique= array_unique($dates);
    asort($datesUnique);
    return $datesUnique;
  }
}