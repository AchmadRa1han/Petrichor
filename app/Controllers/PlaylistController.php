<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\WeatherService;

class PlaylistController extends BaseController
{
    public function index()
    {
        return view('playlist_maker');
    }

    public function login()
    {
        $clientId = env('SPOTIFY_CLIENT_ID');
        $redirectUri = env('SPOTIFY_REDIRECT_URI');
        $scope = 'playlist-modify-public playlist-modify-private user-read-private user-read-email';

        $url = "https://accounts.spotify.com/authorize?" . http_build_query([
            'client_id' => $clientId,
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'scope' => $scope,
            'show_dialog' => 'true'
        ]);

        return redirect()->to($url);
    }

    public function callback()
    {
        $code = $this->request->getGet('code');
        
        if (!$code) {
            return redirect()->to('/')->with('error', 'Login failed');
        }

        $clientId = env('SPOTIFY_CLIENT_ID');
        $clientSecret = env('SPOTIFY_CLIENT_SECRET');
        $redirectUri = env('SPOTIFY_REDIRECT_URI');

        $client = \Config\Services::curlrequest([
            'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]
        ]);

        try {
            $response = $client->post('https://accounts.spotify.com/api/token', [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $redirectUri,
                ],
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($clientId . ':' . $clientSecret)
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            
            session()->set('spotify_token', $data['access_token']);
            session()->set('is_logged_in', true);

            return redirect()->to('/');
        } catch (\Exception $e) {
            return redirect()->to('/')->with('error', 'Token exchange failed');
        }
    }

    public function getWeather()
    {
        $lat = $this->request->getGet('lat');
        $lon = $this->request->getGet('lon');

        if (!$lat || !$lon) {
            return $this->response->setJSON(['error' => 'Coordinates missing']);
        }

        $weatherService = new WeatherService();
        $data = $weatherService->getWeatherByCoords($lat, $lon);

        return $this->response->setJSON($data);
    }

    public function createPlaylist()
    {
        $token = session()->get('spotify_token');
        $weather = $this->request->getPost('weather');
        $city = $this->request->getPost('city');

        if (!$token) return $this->response->setJSON(['error' => 'Unauthorized']);

        $client = \Config\Services::curlrequest([
            'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]
        ]);

        $queries = [
            'Rain'   => 'acoustic chill rainy day',
            'Clouds' => 'lofi dreamy indie',
            'Clear'  => 'sunny happy upbeat',
            'Thunderstorm' => 'dark atmosphere techno',
            'Drizzle' => 'jazz coffee relax',
        ];

        $searchQuery = $queries[$weather] ?? 'mellow chill';

        try {
            // 1. Cari Lagu
            $searchResponse = $client->get('https://api.spotify.com/v1/search', [
                'headers' => ['Authorization' => 'Bearer ' . $token],
                'query' => ['q' => $searchQuery, 'type' => 'track', 'limit' => 5],
                'http_errors' => false
            ]);
            
            $searchData = json_decode($searchResponse->getBody(), true);
            $tracks = $searchData['tracks']['items'] ?? [];
            
            if (empty($tracks)) return $this->response->setJSON(['error' => 'No tracks found for this weather.']);
            $trackUris = array_map(fn($t) => $t['uri'], $tracks);

            // 2. Dapatkan Profil User
            $userResponse = $client->get('https://api.spotify.com/v1/me', [
                'headers' => ['Authorization' => 'Bearer ' . $token],
                'http_errors' => false
            ]);
            
            $userData = json_decode($userResponse->getBody(), true);
            $userId = $userData['id'];

            // 3. Buat Playlist (Set ke PRIVATE)
            $playlistResponse = $client->post("https://api.spotify.com/v1/users/$userId/playlists", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'name'        => "Petrichor Mood: $city",
                    'description' => "Mood for $weather weather. Created by Petrichor.",
                    'public'      => false 
                ],
                'http_errors' => false
            ]);
            
            if ($playlistResponse->getStatusCode() !== 201) {
                return $this->response->setJSON([
                    'error' => 'Playlist Creation Error',
                    'status_code' => $playlistResponse->getStatusCode(),
                    'body' => $playlistResponse->getBody(),
                    'debug_info' => ['logged_in_as' => $userData['email'] ?? 'unknown']
                ]);
            }
            
            $playlistId = json_decode($playlistResponse->getBody(), true)['id'];

            // Beri jeda 2 detik
            sleep(2);

            // 4. Masukkan Lagu
            $addTracksResponse = $client->post("https://api.spotify.com/v1/playlists/$playlistId/tracks", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
                'json' => ['uris' => $trackUris],
                'http_errors' => false
            ]);

            if ($addTracksResponse->getStatusCode() !== 201 && $addTracksResponse->getStatusCode() !== 200) {
                return $this->response->setJSON([
                    'error' => 'Final 403 Error', 
                    'body' => $addTracksResponse->getBody(),
                    'debug_info' => [
                        'playlist_id' => $playlistId,
                        'tracks_found' => $trackUris
                    ]
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'playlist_url' => "https://open.spotify.com/playlist/$playlistId"
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON(['error' => $e->getMessage()]);
        }
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/');
    }
}
