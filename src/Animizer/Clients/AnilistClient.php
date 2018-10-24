<?php

namespace Animizer\Clients;

use Animizer\Data\Anime;

class AnilistClient extends Client
{
    protected $apiUrl = 'https://graphql.anilist.co';

    public function __construct()
    {
        parent::__construct();
    }

    public function get(array $ids, $json_file = null)
    {
        $data = $this->performQuery($ids);

//        die($data);

        $anime['id'] = $data['id'];
        $anime['type'] = $data['format'];
        $anime['url'] = 'anilist.co/' . strtolower($anime['type']) . '/' . $anime['id'];
        $anime['language'] = $data['countryOfOrigin'];
        $anime['adult'] = $data['isAdult'];
        $anime['title'] = $data['title']['english'];
        $anime['title_native'] = $data['title']['native'];
        $anime['title_romaji'] = $data['title']['romaji'];
        $anime['titles'] = [];

        if (!empty($data['startDate']['year']) && !empty($data['startDate']['month']) && !empty($data['startDate']['day'])) {
            $anime['start_date'] = $data['startDate']['year'] . '-' . $data['startDate']['month'] . '-' . $data['startDate']['day'];
        }

        if (!empty($data['endDate']['year']) && !empty($data['endDate']['month']) && !empty($data['endDate']['day'])) {
            $anime['end_date'] = $data['endDate']['year'] . '-' . $data['endDate']['month'] . '-' . $data['endDate']['day'];
        }

        $anime['runtime'] = $data['duration'];
        $anime['poster'] = $data['coverImage']['large'];
        $anime['website'] = null;
        $anime['staffs'] = [];
        $anime['plot'] = $data['description'];
        $anime['genres'] = array_map(function ($item) {
            return ['genre' => $item];
        }, $data['genres']);
        $anime['tags'] = array_map(function ($item) {
            return [
                'id' => $item['id'],
                'tag' => $item['name'],
                'description' => $item['description'],
                'adult' => $item['isAdult'],
            ];
        }, $data['tags']);
        $anime['characters'] = [];
        $anime['episode_count'] = $data['episodes'];
        $anime['episodes'] = [];
        $anime['franchise'] = [];
        $anime['sources'] = !empty($data['idMal']) ? [
            ['id' => $data['idMal'], 'url' => 'https://myanimelist.net/anime/' . $data['idMal']],
        ] : null;

        return new Anime($anime);
    }

    private function performQuery(array $ids)
    {
        $query = 'query ($id: Int, $idMal: Int) {
          Media(id: $id, idMal: $idMal) {
            id
            idMal
            type
            format
            title {
              romaji
              english
              native
              userPreferred
            }
            synonyms
            countryOfOrigin
            isAdult
            status
            description
            startDate {
              year
              month
              day
            }
            endDate {
              year
              month
              day
            }
            season
            duration
            trailer {
              id
              site
            }
            coverImage {
              large
              medium
            }
            externalLinks {
              id
              site
              url
            }
            genres
            episodes
            chapters
            volumes
            tags {
              id
              name
              description
              category
              isAdult
              rank
            }
            staff(perPage: 500, sort: ROLE) {
              edges {
                id
                role
                node {
                  id
                  name {
                    first
                    last
                    native
                  }
                  image {
                    large
                    medium
                  }
                }
              }
            }
            characters(perPage: 500, sort: ROLE) {
              edges {
                id
                role
                node {
                  id
                  name {
                    first
                    last
                    native
                  }
                  description
                  siteUrl
                }
                voiceActors {
                  id
                  name {
                    first
                    last
                    native
                  }
                  language
                  image {
                    large
                    medium
                  }
                  description
                }
              }
            }
            studios {
              edges {
                id
                isMain
                node {
                  id
                  name
                  siteUrl
                }
              }
            }
            relations {
              edges {
                id
                relationType
                node {
                  id
                  idMal
                  title {
                    romaji
                    english
                    native
                    userPreferred
                  }
                }
              }
            }
          }
        }
        ';

        if (isset($ids['id'])) {
            $variables['id'] = $ids['id'];
        } elseif (isset($ids['mal'])) {
            $variables['idMal'] = $ids['mal'];
        } else {
            throw new \Exception('id or idMal required to perform request');
        }

        $response = $this->guzzleClient->request('POST', 'https://graphql.anilist.co', [
            'json' => [
                'query' => $query,
                'variables' => $variables,
            ]
        ]);

        $data = $this->toArray((string)$response->getBody());
        if (isset($data['data']['Media'])) {
            return $data['data']['Media'];
        }

        return null;
    }
}