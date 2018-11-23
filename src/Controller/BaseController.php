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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

    public function renderHome(APIService $api_service, SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
        $this->api_service = $api_service;

        $this->repo_uri = self::GITHUB_API_URI . self::OUR_PROJECT_URI;
        $this->commit_history = new CommitHistory();
        $this->repo = new RepoOverview();

        $this->generateCommitHistory();


        // TODO: only consider a function to have been "Changed" if something changed between commits
        //      this is because the file may be changed in a commit, but not that specific function
        return $this->render('home.html.twig',
          [ 'files' => $this->repo->getFiles(),
            'colours' => $this->generateColors($this->repo->getFiles()),
            'dates' => $this->repo->getCommitDates()]);
    }

    private function generateColors($files) {
      $colours = [];
      foreach ($files as $file) {
        $colour = $this->getNewColour($colours);
        $file_name = $file->getName();
        $colours[$file_name] = $colour;
      }
      return $colours;
    }

    private function getRandomColour() {
      $letters = str_split('0123456789ABCDEF');
      $colour = '#';
      for ($i = 0; $i < 6; $i++) {
        $colour .= $letters[random_int(0, 15)];
      }
      return $colour;
    }

    private function getNewColour($existing_colours) {
      $random_colour = $this->getRandomColour();
      while (in_array($random_colour, $existing_colours)) {
        $random_colour = $this->getRandomColour();
      }
      return $random_colour;
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

        $i = 'i'; // temp for testing
    }

    private function parseCommit($commit_all)
    {
//        $commit = $commit_all['commit'];
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

                            if ($file_name == 'src/Entities/Chart.php') { // temp testing function
                                $i = 1;
                            }
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