<?php
/**
 * Created by PhpStorm.
 * User: matthewridderikhoff
 * Date: 2018-11-19
 * Time: 1:42 PM
 */

namespace App\Services;


use GuzzleHttp\Client;
use function GuzzleHttp\Psr7\parse_header;
use Symfony\Component\Serializer\SerializerInterface;

class APIService
{
    private $client;
    private $serializer;
    private $auth_array;

    public function __construct(SerializerInterface $serializer)
    {
        $this->client = new Client();
        $this->serializer = $serializer;
        
        $github_auth_token = file_get_contents('github_auth_token.txt');
        $this->auth_array = ['headers' => ['Authorization' => 'token ' . $github_auth_token]];
    }

    public function getAllCommits($repo_uri) {
        $response = $this->client->request('GET', $repo_uri.'commits', $this->auth_array);
        $response_contents = $response->getBody()->getContents();
        $commits = $this->serializer->decode($response_contents, 'json');
        $link_header = parse_header($response->getHeader('Link'));

        while ($this->moreLinks($link_header)) {

            $response = $this->client->request('GET', $this->nextLink($link_header), $this->auth_array);
            $response_contents = $response->getBody()->getContents();
            $next_commits = $this->serializer->decode($response_contents, 'json');

            $commits = array_merge($commits, $next_commits);
            $link_header = parse_header($response->getHeader('Link'));
        }

        return $commits;
    }

    public function getAllCommitInfo($repo_uri)
    {
        $commits = $this->getAllCommits($repo_uri);

        $all_info_commits = [];
        foreach ($commits as $commit) {
            $commit_info = $this->getCommit($repo_uri, $commit['sha']);

            $all_info_commits[$commit['sha']]['commit_info'] = $commit_info;
            $all_info_commits[$commit['sha']]['commit'] = $commit;
        }

        $json_commits = $this->serializer->encode($all_info_commits, 'json');
        file_put_contents('commits.json', $json_commits);

        $results = file_get_contents('commits.json');
        return $this->serializer->decode($results, 'json');
    }

    public function getCommit($repo_uri, $commit_sha) {
        $response = $this->client->request('GET', $repo_uri.'commits/'.$commit_sha, $this->auth_array);
        $response_contents = $response->getBody()->getContents();
        return $this->serializer->decode($response_contents, 'json');
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