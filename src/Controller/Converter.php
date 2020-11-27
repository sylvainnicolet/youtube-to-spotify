<?php

namespace App\Controller;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class Converter
{
    protected $params = array();
    protected $request = null;
    
    protected $twig;

    public function __construct(Environment $twig) {
        $this->twig = $twig;
    }

    public function index() {
        return $this->render('converter.html.twig');
    }

    public function getYoutubePlaylist() {
        try {
            $url = $_GET['playlist_url'];
            $res = parse_url($url);
            parse_str($res['query'], $params);
            $playlistId = $params['list'];
        } catch (Exception $e) {
            $data['error'] = 'Invalid URL or Playlist Id.';
            return $this->render('error.html.twig', $data);
        }

        $maxResults = 50;
        $api_key = 'api_key';
        $api_url = 'https://youtube.googleapis.com/youtube/v3/playlistItems?part=snippet&maxResults='. $maxResults .'&playlistId='. $playlistId .'&key=' .$api_key;

        try {
            $response = json_decode(file_get_contents($api_url), true);
        } catch (Exception $e) {
            $data['error'] = 'Invalid URL or Playlist Id.';
            return $this->render('error.html.twig', $data);
        }

        $playlist = $response['items'];
        $playlist = $this->playlistAddTitle($playlist);

        $data['videos'] = $playlist['videos'];

        return $this->render('list_videos.html.twig', $data);
    }

    protected function render(string $path, array $variables =[]) {
        $html = $this->twig->render($path, $variables);
        return new Response($html);
    }

    protected function playlistAddTitle($playlist) {
        $titles = [];
        foreach ($playlist as $key => $video) {
            $titles[$key]['title'] = $video['snippet']['title'];
        }
        $playlist['videos'] = $titles;

        return $playlist;
    }
}
