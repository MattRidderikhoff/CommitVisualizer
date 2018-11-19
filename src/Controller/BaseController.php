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
use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class BaseController extends AbstractController
{
    private $serializer;
    private $client;
    private $commit_history;

    const TEST_URI = 'https://api.github.com/repos/octocat/Hello-World/';
    const OUR_PROJECT_URI = 'https://api.github.com/repos/MattRidderikhoff/DashboardGenerator/';

    public function renderHome(SerializerInterface $serializer) {

        $this->serializer = $serializer;
        $this->client = new Client(['base_uri' => self::OUR_PROJECT_URI, 'defaults' => ['header' => ['Authorization' => 'Bearer '.'591f9410b8e503445b4d54fe008255a043c13b69']]]);
        $this->commit_history = new CommitHistory();

        $this->generateCommitHistory($serializer);

        return $this->render('home.html.twig',
            []);
    }

    private function generateCommitHistory() {

        $response = $this->client->request('GET', 'commits');
        $response_contents = $response->getBody()->getContents();

        $commits = $this->serializer->decode($response_contents, 'json');

        foreach ($commits as $commit) {
            $this->parseCommit($commit);
        }
    }

    private function parseCommit($commit) {
        $commit_sha = $commit['sha'];

        $response = $this->client->request('GET', 'commits/'.$commit_sha);
        $response_contents = $response->getBody()->getContents();
        $commit_info = $this->serializer->decode($response_contents, 'json');

        // contains high-level about additions and deletions
        $stats = $commit_info['stats'];
        $file_stats = $commit_info['files'];

        foreach ($commit_info['files'] as $file) {

            if ($file['status'] == 'added') {
                // only track PHP files
                if (strpos($file['filename'], '.php') !== false) {
                    $file_lifespan = new FileLifespan($file);
                }

            } else {

            }
        }

    }
}