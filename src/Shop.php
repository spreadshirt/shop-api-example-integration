<?php

require_once './decodeJsonResponse.php';

// the new Shop API is only available as JSON and therefore does not need this parameter
// older parts of the Spreadshirt API require it as they receive/return XML by default
const API_JSON_PARAM = '?mediaType=json';

class Shop {
  private $client;
  private $productTypeCache;

  public function __construct($apiUrl, $shopId, $authorizationHeader) {
    $this->client = new GuzzleHttp\Client(['base_uri' => $apiUrl . '/shops/' . $shopId . '/', 'headers' => $authorizationHeader]);

    $this->productTypeCache = new Memcached();
    $this->productTypeCache->addServer('memcached', '11211');
  }

  public function getSellables($page = 0) {
    $response = $this->client->request('GET', 'sellables?page=' . $page);
    return decodeJsonResponse($response);
  }

  public function getSellable($sellableId, $ideaId, $appearanceId) {
    $response = $this->client->request('GET', 'sellables/' . $sellableId . '?ideaId=' . $ideaId . '&appearanceId=' . $appearanceId);
    return decodeJsonResponse($response);
  }

  public function getProductTypeData($productTypeId) {
    $productTypeData = $this->productTypeCache->get($productTypeId);

    if (!$productTypeData) {
      $productTypeResponse = $this->client->request('GET', 'productTypes/' . $productTypeId . API_JSON_PARAM);
      $productTypeData = decodeJsonResponse($productTypeResponse);
      $this->productTypeCache->set($productTypeId, $productTypeData, time() + 3600); // 1 hour
    }

    return $productTypeData;
  }
}
