<?php

session_start();

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/Shop.php';
require __DIR__ . '/Basket.php';

const API_URL = 'https://api.spreadshirt.net/api/v1';
const API_KEY = '';
const USER_AGENT = 'ShopApiExampleIntegration-1.0';
const SHOP_ID = 100229382;

if (empty(API_KEY)) {
  exit('Please configure your API_KEY. See https://developer.spreadshirt.net/display/API/Security for further information');
}

$requiredHeaders = [
  'Authorization' => 'SprdAuth apiKey="' . API_KEY . '"',
  'User-Agent' => USER_AGENT
];

$shop = new Shop(API_URL, SHOP_ID, $requiredHeaders);
$basket = new Basket(API_URL, SHOP_ID, $requiredHeaders);
$twig = new \Twig\Environment(new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates'));
$basket->addBasketToRenderer($twig);

$sellableId = isset($_GET['sellableId']) ? $_GET['sellableId'] : null;
$ideaId = isset($_GET['ideaId']) ? $_GET['ideaId'] : null;
$appearanceId = isset($_GET['appearanceId']) ? $_GET['appearanceId'] : null;

// very basic routing
if ($sellableId && $ideaId && $appearanceId) {
  // show detail page
  $detailData = $shop->getSellable($sellableId, $ideaId, $appearanceId);

  if (isset($_POST['action']) && $_POST['action'] === 'addToBasket' && isset($_POST['sizeId'])) {
    $basket->addItem($sellableId, $appearanceId, $_POST['sizeId'], $_POST['quantity']);
  }

  $productTypeData = $shop->getProductTypeData($detailData->productTypeId);

  $appearances = array_reduce($productTypeData->appearances, function ($carry, $item) {
    $carry[$item->id] = $item->name;
    return $carry;
  }, []);

  $sizes = array_reduce($productTypeData->sizes, function ($carry, $item) {
    $carry[$item->id] = $item->name;
    return $carry;
  }, []);

  $sellableParams = 'sellableId=' . $sellableId . '&ideaId=' . $ideaId;

  echo $twig->render('detail.html', [
    'sellable' => $detailData,
    'appearances' => $appearances,
    'currentAppearance' => $appearanceId,
    'sizes' => $sizes,
    'sellableParams' => $sellableParams,
  ]);
  exit;
}

// show list page
$page = isset($_GET['page']) ? intval($_GET['page']) - 1 : 0;
$listData = $shop->getSellables($page);
echo $twig->render('index.html', [
  'sellables' => $listData->sellables,
  'currentPage' => floor($listData->offset / $listData->limit) + 1,
  'maxPage' => ceil($listData->count / $listData->limit)
]);
