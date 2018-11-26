<?php
/**
 * Created by PhpStorm.
 * User: matthewridderikhoff
 * Date: 2018-09-27
 * Time: 9:07 PM
 */

namespace App\Controller;

use App\Entities\CommitHistory;
use App\Entities\FileLifespan;
use App\Entities\RepoOverview;
use App\Services\APIService;
use App\Services\ColourService;
use App\Services\FormHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

class BaseController extends AbstractController
{
    private $serializer;
    private $api_service;

    private $commit_history;
    private $repo_uri;
    private $repo;

    const TEST_URI = 'https://api.github.com/repos/octocat/Hello-World/';

    const GITHUB_API_URI = 'https://api.github.com/';
    const OUR_PROJECT_URI = 'repos/MattRidderikhoff/DashboardGenerator/';

    public function renderHome(APIService $api_service, ColourService $colour_service, Request $request, FormHandler $form_handler, SerializerInterface $serializer)
    {
        $form_handler->handleHomeRequest($request);

        $this->serializer = $serializer;
        $this->api_service = $api_service;

        $this->repo_uri = self::GITHUB_API_URI . self::OUR_PROJECT_URI;
        $this->commit_history = new CommitHistory();
        $this->repo = new RepoOverview();

        $this->generateCommitHistory();

        $files = $this->repo->getFiles();
        $filteredFiles = $this->repo->getFilteredFiles($form_handler->getFiles());
        if (empty($filteredFiles)) {
            $filteredFiles = $files;
        }

        $dates = $this->repo->getCommitDates();
        $startDate = $form_handler->getStartDate();
        if (is_null($startDate)) {
            $startDate = current($dates);
        }

        $endDate = $form_handler->getEndDate();
        if (is_null($endDate)) {
            $endDate = end($dates);
        }
        $functionNames = $this->repo->getFunctionNames();

        return $this->render('home.html.twig',
            [ 'files' => $files,
              'colours' => $colour_service->generateColors($this->repo->getFiles()),
              'dates' => $dates,
              'endDate' => $endDate,
              'functions' => $functionNames,
              'filteredFiles' => $filteredFiles,
              'startDate' => $startDate
            ]);
    }

    private function generateCommitHistory()
    {
//        $all_commit_info = $this->api_service->getAllCommitInfo($this->repo_uri);  // online version
        $all_commit_info = $this->getAllCommitInfoSaved(); // offline version

        // order commits chronologically
        usort($all_commit_info,
            function($a, $b) {
                $a_info = $a['commit_info']['commit']['committer']['date'];
                $b_info = $b['commit_info']['commit']['committer']['date'];

                $date_a = new \DateTime($a_info);
                $date_b = new \DateTime($b_info);

                return $date_a > $date_b;
            });

        foreach ($all_commit_info as $commit) {
            $this->parseCommit($commit);
        }

        // now look remove commits from functions, where the function wasn't impacted itself
        $this->removeUnnecessaryCommits();
    }

    private function removeUnnecessaryCommits() {
        foreach ($this->repo->getFiles() as $file) {
            $file->removeUnnecessaryCommits();
        }
    }

    private function parseCommit($commit_all)
    {
        $commit_info = $commit_all['commit_info'];
        $commit_date_raw = $commit_all['commit_info']['commit']['committer']['date'];
        $commit_date = new \DateTime($commit_date_raw);

        foreach ($commit_info['files'] as $file) {
            $file_name = $file['filename'];

            // only track PHP files
            if (strpos($file_name, '.php') !== false) {

                if (strpos($file_name, 'vendor') === false &&
                   (strpos($file_name, 'src/') !== false)) { // only include user-generated files from DashboardCreator

                    if ($file['status'] == 'added') {

                        if (!$this->repo->hasFile($file_name)) {

                            $file_lifespan = new FileLifespan($file, $commit_date);
                            $this->repo->addFile($file_lifespan);
                        }

                    } elseif ($file['status'] == 'modified') {
                        $this->repo->modifyFile($file, $commit_date);

                    } elseif ($file['status'] == 'renamed') {
                        // TODO
                    }
                }
            }
        }
    }

    private function getAllCommitInfoSaved() {
        $results = file_get_contents('commits.json');
        return $this->serializer->decode($results, 'json');
    }
}