<?php
/**
 * Created by PhpStorm.
 * User: matthewridderikhoff
 * Date: 2018-09-27
 * Time: 9:07 PM
 */

namespace App\Controller;

use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class BaseController extends AbstractController
{
    private $serializer;
    private $client;

    const TEST_URI = 'https://api.github.com/repos/octocat/Hello-World/';
    const OUR_PROJECT_URI = 'https://api.github.com/repos/MattRidderikhoff/DashboardGenerator';

    public function renderHome(SerializerInterface $serializer) {

        $this->serializer = $serializer;
        $this->client = new Client(['base_uri' => 'https://api.github.com/repos/MattRidderikhoff/DashboardGenerator']);

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

    }
}