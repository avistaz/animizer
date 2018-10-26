<?php

namespace Animizer\Clients;

use Animizer\Data\Anime;
use Animizer\Data\Person;

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

        $url_type = (strtolower($data['format']) == 'manga') ? 'manga' : 'anime';

        $anime['id'] = $data['id'];
        $anime['type'] = $data['format'];
        $anime['url'] = 'anilist.co/' . $url_type . '/' . $anime['id'];
        $anime['language'] = strtolower($data['countryOfOrigin']);
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

        if (isset($data['characters']['edges'])) {
            $characters = $data['characters']['edges'];
            $anime['characters'] = array_map(function ($item) {
                $full_name = '';
                if (!empty($item['node']['name']['first'])) {
                    $full_name = $item['node']['name']['first'];
                }
                if (!empty($item['node']['name']['last'])) {
                    $full_name .= ' ' . $item['node']['name']['last'];
                }
                $full_name = trim($full_name);
                if (empty($full_name) && !empty($item['node']['name']['native'])) {
                    $full_name = $item['node']['name']['native'];
                }

                $actor = [];

                $voiceActors = collect($item['voiceActors']);
                if ($voiceActors->count()) {
                    $voiceActor = $voiceActors->whereNotIn('language', ['ENGLISH'])->first();
                    if ($voiceActor) {
                        $actor = new Person([
                            'id' => $voiceActor['id'],
                            'name' => $voiceActor['name']['first'] . ' ' . $voiceActor['name']['last'],
                            'name_native' => $voiceActor['name']['native'],
                            'biography' => $voiceActor['description'],
                            'photo' => $voiceActor['image']['large'] ?? $voiceActor['image']['medium'] ?? null,
                        ]);
                    }
                }

                return [
                    'id' => $item['node']['id'] ?? null,
                    'type' => $item['role'] ?? null,
                    'name' => $full_name ?? null,
                    'description' => $item['node']['description'] ?? null,
                    'picture' => $item['node']['image']['large'] ?? $item['node']['image']['medium'] ?? null,
                    'actor' => $actor,
                ];
            }, $characters);
        }

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
            description(asHtml: true)
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
                  description(asHtml: true)
                  siteUrl
                  image {
                    large
                    medium
                  }
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
                  description(asHtml: true)
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