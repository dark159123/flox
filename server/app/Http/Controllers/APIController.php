<?php

  namespace Flox\Http\Controllers;

  use DateTime;
  use Flox\Item;
  use Flox\Category;
  use Flox\Http\Controllers\Controller;
  use GuzzleHttp\Client;

  class APIController extends Controller {

    public function homeItems($category, $orderBy)
    {
      return $this->getItems($category, $orderBy, 5);
    }

    public function categoryItems($category, $orderBy)
    {
      return $this->getItems($category, $orderBy, 20);
    }

    public function allCategories()
    {
      return Category::all();
    }

    public function slugItem($slug)
    {
      return Item::where('slug', $slug)->first();
    }

    private function getItems($category, $orderBy, $count)
    {
      $category = Category::where('slug', $category)->with('itemsCount')->first();

      $items = Item::where('category_id', $category->id)->orderBy($orderBy, 'desc')->take($count)->get();

      return [
        'items' => $items,
        'category' => $category
      ];
    }

     public function searchFloxByTitle($title)
     {
       // todo: Implement Levenshtein ;)
       return Item::where('title', 'LIKE', '%' . $title . '%')->with('categories')->get();
     }

    public function searchTMDBByTitle($title)
    {
      $items = [];
      $client = new Client(['base_uri' => 'http://api.themoviedb.org/']);

      $response = $client->get('/3/search/multi', ['query' => ['api_key' => env('TMDB_API_KEY'), 'query' => $title]]);
      $response = json_decode($response->getBody());

      foreach($response->results as $result) {
        if($result->media_type == 'person') continue;

        $dtime = DateTime::createFromFormat('Y-m-d', (array_key_exists('release_date', $result)
          ? ($result->release_date ?: '1970-12-1')
          : ($result->first_air_date ?: '1970-12-1')
        ));

        $items[] = [
          'tmdb_id' => $result->id,
          'title' => array_key_exists('name', $result) ? $result->name : $result->title,
          'poster' => $result->poster_path,
          'released' => $dtime->getTimestamp(),
        ];
      }

      return $items;
    }
  }