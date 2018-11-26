<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\Request;

class FormHandler
{
  private $files = [];
  private $startDate;
  private $endDate;

    public function handleHomeRequest(Request $request) {
        $filter_by_file = $request->query->get('files');
        if (isset($filter_by_file)) {
            $this->filterByFile($filter_by_file);
        }

        $filter_by_start_date = $request->query->get('start_date');
        $filter_by_end_date = $request->query->get('end_date');
        if (isset($filter_by_start_date) && isset($filter_by_end_date)) {
            $this->filterByDate($filter_by_start_date, $filter_by_end_date);
        }
    }

    private function filterByFile($filteredFiles) {
      return $this->files = $filteredFiles;
    }

    public function getFiles() {
      return $this->files;
    }

    private function filterByDate($startDate, $endDate) {
      $this->startDate = $startDate;
      $this->endDate = $endDate;
    }

    public function getStartDate() {
      return $this->startDate;
    }

    public function getEndDate() {
      return $this->endDate;
    }
}