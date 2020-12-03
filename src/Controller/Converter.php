<?php

namespace App\Controller;

use Exception;
use SpotifyWebAPI\Session;
use SpotifyWebAPI\SpotifyWebAPI;
use stdClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
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
        return $this->render('login.html.twig');
    }

    public function authSpotify() {
        $session = new Session(
            'clientId',
            'clientSecret',
            'redirect_uri'
        );

        $api = new SpotifyWebAPI();

        if (isset($_GET['code'])) {
            $session->requestAccessToken($_GET['code']);
            $api->setAccessToken($session->getAccessToken());

            // Store token
            $sessionHttp = new \Symfony\Component\HttpFoundation\Session\Session();
            $sessionHttp->start();
            $sessionHttp->set('token',$session->getAccessToken());

            return $this->render('converter.html.twig');
        } else {
            $options = [
                'scope' => [
                    'playlist-modify-private',
                    'playlist-modify-public'
                ],
            ];

            header('Location: ' . $session->getAuthorizeUrl($options));
            die();
        }
    }

    public function getYoutubePlaylist() {
        // Youtube
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
        $playlistYoutube = $response['items'];

        // Spotify
        $playlistSpotify = $this->convertYoutubePlaylistToSpotify($playlistYoutube);

        $data['playlist'] = $playlistSpotify;
        return $this->render('list_videos.html.twig', $data);
    }

    protected function render(string $path, array $variables =[]) {
        $html = $this->twig->render($path, $variables);
        return new Response($html);
    }

    protected function convertYoutubePlaylistToSpotify($playlistYoutube) {
        $sessionHttp = new \Symfony\Component\HttpFoundation\Session\Session();
        $api = new SpotifyWebAPI();
        $api->setAccessToken($sessionHttp->get('token'));

        $playlistSpotify = [];
        foreach ($playlistYoutube as $key=>$video) {
            $title = $video['snippet']['title'];
            $tracks = json_decode(json_encode($api->search($title, 'track')->tracks->items), true);
            if (!empty($tracks)) {
                $playlistSpotify[$key] = $tracks[0];
            }
            $playlistSpotify[$key]['youtube_title'] = $title;
        }
        return $playlistSpotify;
    }

    protected function addTracksToPlaylist($tracks) {

    }
}
