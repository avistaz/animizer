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

        $anime['id'] = $data['id'];
        $anime['type'] = $data['type'];
        $anime['language'] = $data['countryOfOrigin'];
        $anime['adult'] = $data['isAdult'];
        $anime['title'] = $data['title']['english'];
        $anime['title_native'] = $data['title']['native'];
        $anime['title_romaji'] = $data['title']['romaji'];
        $anime['titles'] = [];
        $anime['start_date'] = $data['startDate']['year'] . '-' . $data['startDate']['month'] . '-' . $data['startDate']['day'];
        $anime['end_date'] = $data['endDate']['year'] . '-' . $data['endDate']['month'] . '-' . $data['endDate']['day'];
        $anime['runtime'] = $data['duration'];
        $anime['poster'] = $data['coverImage']['large'];
        $anime['website'] = null;
        $anime['creators'] = [];
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
        $anime['episode_count'] = '';
        $anime['episodes'] = [];
        $anime['franchise'] = [];

        return new Anime($anime);
    }

    private function performQuery(array $ids)
    {
        $query = 'query ($id: Int, $idMal: Int) {
          Media(id: $id, idMal: $idMal) {
            id
            idMal
            type
            title {
              romaji
              english
              native
              userPreferred
            }
            synonyms
            countryOfOrigin
            isAdult
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
            tags {
              id
              name
              description
              category
              isAdult
              rank
            }
            staff(perPage: 100, sort: ROLE) {
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
            characters(perPage: 100, sort: ROLE) {
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
            throw new \Exception('Ids required to perform request');
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