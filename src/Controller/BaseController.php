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

    private $files = [];

    const TEST_URI = 'https://api.github.com/repos/octocat/Hello-World/';

    const GITHUB_API_URI = 'https://api.github.com/';
    const OUR_PROJECT_URI = 'repos/MattRidderikhoff/DashboardGenerator/';


    const AUTH_ARRAY = ['headers' => ['Authorization' => 'token c368b842e3e756e573997c2f79c38e6c5a3dbc4d']];

    public function renderHome(APIService $api_service, SerializerInterface $serializer)
    {

        $this->serializer = $serializer;
        $this->api_service = $api_service;

        $this->repo_uri = self::GITHUB_API_URI . self::OUR_PROJECT_URI;
        $this->commit_history = new CommitHistory();
        $this->repo = new RepoOverview();

        $this->generateCommitHistory();

        return $this->render('home.html.twig', []);
    }

    private function generateCommitHistory()
    {
//        $all_commit_info = $this->api_service->getAllCommitInfo($this->repo_uri);  // online version
        $all_commit_info = $this->getAllCommitInfoSaved(); // offline version

        foreach ($all_commit_info as $commit) {
            $this->parseCommit($commit);
        }
    }

    private function parseCommit($commit_all)
    {
        $commit = $commit_all['commit'];
        $commit_info = $commit_all['commit_info'];

        // contains high-level about additions and deletions
        $stats = $commit_info['stats'];
        $file_stats = $commit_info['files'];

        foreach ($commit_info['files'] as $file) {
            $file_name = $file['filename'];

            // only track PHP files
            if (strpos($file_name, '.php') !== false && strpos($file_name, 'vendor') === false) { // 2nd condition is ignoring certain files from our old project`x

                if ($file['status'] == 'added') {

                    if (!$this->repo->hasFile($file_name)) {

                        $file_lifespan = new FileLifespan($file);
                        $this->files[] = $file_lifespan;
                    }

                } else {

                }
            }
        }
    }

    private function getAllCommitInfoSaved() {
        $results = file_get_contents('commits.json');
        return $this->serializer->decode($results, 'json');
    }
}