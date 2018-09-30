<?php

namespace Animizer\Clients;

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