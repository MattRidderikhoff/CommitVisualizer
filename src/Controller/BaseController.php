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
use function GuzzleHttp\Psr7\parse_header;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class BaseController extends AbstractController
{
    private $serializer;
    private $client;
    private $commit_history;
    private $repo_uri;

    private $files = [];

    const TEST_URI = 'https://api.github.com/repos/octocat/Hello-World/';

    const GITHUB_API_URI = 'https://api.github.com/';
    const OUR_PROJECT_URI = 'repos/MattRidderikhoff/DashboardGenerator/';


    const AUTH_ARRAY = ['headers' => ['Authorization' => 'token c368b842e3e756e573997c2f79c38e6c5a3dbc4d']];

    public function renderHome(SerializerInterface $serializer) {

        $this->serializer = $serializer;
//        $this->client = new Client(['base_uri' => self::OUR_PROJECT_URI, 'defaults' => ['header' => ['Authorization' => 'Bearer '.'591f9410b8e503445b4d54fe008255a043c13b69']]]);

        $this->repo_uri = self::GITHUB_API_URI.self::OUR_PROJECT_URI;
        $this->client = new Client();
        $this->commit_history = new CommitHistory();

        $this->generateCommitHistory($serializer);

        return $this->render('home.html.twig', []);
    }

    private function generateCommitHistory() {

        $response = $this->client->request('GET', $this->repo_uri.'commits', self::AUTH_ARRAY);
        $response_contents = $response->getBody()->getContents();
        $commits = $this->serializer->decode($response_contents, 'json');
        $link_header = parse_header($response->getHeader('Link'));

        while ($this->moreLinks($link_header)) {

            $response = $this->client->request('GET', $this->nextLink($link_header), self::AUTH_ARRAY);
            $response_contents = $response->getBody()->getContents();
            $next_commits = $this->serializer->decode($response_contents, 'json');

            $commits = array_merge($commits, $next_commits);
            $link_header = parse_header($response->getHeader('Link'));
        }
        
        foreach ($commits as $commit) {
            $this->parseCommit($commit);
        }

    }

    private function parseCommit($commit) {
        $commit_sha = $commit['sha'];

        $response = $this->client->request('GET', $this->repo_uri.'commits/'.$commit_sha, self::AUTH_ARRAY);
        $response_contents = $response->getBody()->getContents();
        $commit_info = $this->serializer->decode($response_contents, 'json');

        // contains high-level about additions and deletions
        $stats = $commit_info['stats'];
        $file_stats = $commit_info['files'];

        foreach ($commit_info['files'] as $file) {

            // only track PHP files
            if (strpos($file['filename'], '.php') !== false) {

                if ($file['status'] == 'added') {
                    $file_lifespan = new FileLifespan($file);
                    $this->files[] = $file_lifespan;
                } else {

                }
            }
        }
    }

    private function moreLinks($link_header) {
        $more_links = false;

        foreach ($link_header as $link) {
            if ($link['rel'] == 'next') {
                $more_links = true;
            }
        }

        return $more_links;
    }

    private function nextLink($link_header) {
        foreach ($link_header as $link) {
            if ($link['rel'] == 'next') {
                $link_uri = $link[0];
                $link_uri = str_replace('<', '', $link_uri);
                $link_uri = str_replace('>', '', $link_uri);
                return $link_uri;
            }
        }

        return null;
    }
}