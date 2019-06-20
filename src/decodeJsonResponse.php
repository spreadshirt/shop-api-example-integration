<?php

/**
 * @param $response
 * @return mixed
 */
function decodeJsonResponse($response) {
  return json_decode($response->getBody()->getContents());
}
