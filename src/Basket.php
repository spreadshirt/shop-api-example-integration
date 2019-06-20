<?php

require_once './decodeJsonResponse.php';

const API_JSON_PARAM = '?mediaType=json';

class Basket {
  private $client;
  private $shopId;
  private $basket;

  public function __construct($apiUrl, $shopId, $authorizationHeader) {
    $this->client = new GuzzleHttp\Client(['base_uri' => $apiUrl . '/baskets', 'headers' => $authorizationHeader]);

    $this->shopId = $shopId;

    if (isset($_SESSION['basket'])) {
      $this->basket = $_SESSION['basket'];
    } else {
      $this->basket = null;
    }
  }

  public function getBasket($reload = false) {
    if ($this->basket && $reload) {
      $response = $this->client->request('GET', 'baskets/' . $this->basket->id . API_JSON_PARAM);
      $this->basket = decodeJsonResponse($response);
      $_SESSION['basket'] = $this->basket;
    }

    return $this->basket;
  }

  public function addItem($sellableId, $appearanceId, $size, $quantity) {
    $this->sendItemToBasketService($sellableId, $appearanceId, $size, $quantity);
    $this->getBasket(true);
  }

  private function sendItemToBasketService($sellableId, $appearanceId, $size, $quantity) {
    $basket = $this->getBasket();
    if ($basket) {
      foreach ($basket->basketItems as $basketItem) {
        $element = $basketItem->element;
        $hasSameSize = false;
        $hasSameAppearance = false;

        if ($element->id === $sellableId) {
          foreach ($element->properties as $property) {
            if ($property->key == 'size' && $property->value == $size) $hasSameSize = true;
            if ($property->key == 'appearance' && $property->value == $appearanceId) $hasSameAppearance = true;

            if ($hasSameSize && $hasSameAppearance) {
              // update quantity of item
              $this->client->request('PUT', 'baskets/' . $basket->id . '/items/' . $basketItem->id . API_JSON_PARAM, [
                'json' => $this->getBasketItem($sellableId, $size, $appearanceId, $quantity + $basketItem->quantity)
              ]);

              return;
            }
          }
        }
      }

      // add new basket item
      $this->client->request('POST', 'baskets/' . $basket->id . '/items?mediaType=json', [
        'json' => $this->getBasketItem($sellableId, $size, $appearanceId, $quantity)
      ]);

      return;
    }

    // create new basket with new item
    $response = $this->client->request('POST', API_JSON_PARAM, [
      'json' => [
        'basketItems' => [
          $this->getBasketItem($sellableId, $size, $appearanceId, $quantity)
        ]
      ]
    ]);
    $basketData = decodeJsonResponse($response);

    // $basketData only contains an id and a link but that's ok because we get the whole basket later
    $this->basket = $basketData;
  }

  private function getBasketItem($sellableId, $size, $appearanceId, $quantity) {
    return [
      'element' => [
        'id' => $sellableId,
        'type' => 'sprd:sellable',
        'properties' => [
          ['key' => 'size', 'value' => $size],
          ['key' => 'appearance', 'value' => $appearanceId]
        ],
        'shop' => ['id' => $this->shopId]
      ],
      'quantity' => $quantity
    ];
  }

  /*
   * this method adds the basket object and a currency formatter to twig
   * contains no API specific code
   */
  public function addBasketToRenderer($renderer) {
    $renderer->addExtension(new class($this) extends \Twig\Extension\AbstractExtension implements \Twig\Extension\GlobalsInterface {
      private $basket;

      public function __construct($basket) {
        $this->basket = $basket;
      }

      public function getGlobals() {
        return [
          'basket' => $this->basket->getBasket(),
        ];
      }

      public function getFilters() {
        return [
          new \Twig\TwigFilter('formatCurrency', [$this, 'formatCurrency']),
        ];
      }

      public function formatCurrency($value, $currency = '€') {
        return number_format($value, 2, ',', ' ') . $currency;
      }
    });
  }
}
