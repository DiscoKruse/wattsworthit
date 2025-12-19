<?php
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");

function get_json($url) {
  $ctx = stream_context_create([
    "http" => [
      "method" => "GET",
      "header" => "User-Agent: watts-worth-it/1.0\r\n"
    ]
  ]);
  $raw = @file_get_contents($url, false, $ctx);
  if ($raw === false) { return null; }
  return json_decode($raw, true);
}

function get_text($url) {
  $ctx = stream_context_create([
    "http" => [
      "method" => "GET",
      "header" => "User-Agent: watts-worth-it/1.0\r\n"
    ]
  ]);
  $raw = @file_get_contents($url, false, $ctx);
  if ($raw === false) { return null; }
  return trim($raw);
}

// Price (CoinGecko simple price) :contentReference[oaicite:4]{index=4}
$price = get_json("https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=usd");
$usdPerBtc = isset($price["bitcoin"]["usd"]) ? floatval($price["bitcoin"]["usd"]) : 0.0;

// Tip height + difficulty (mempool endpoints) :contentReference[oaicite:5]{index=5}
$mempoolBase = "https://mempool.space";
$heightText = get_text("$mempoolBase/api/blocks/tip/height");
$height = $heightText ? intval($heightText) : 0;

$diffJson = get_json("$mempoolBase/api/v1/difficulty-adjustment");
$difficulty = 0.0;
if ($diffJson) {
  if (isset($diffJson["difficulty"])) $difficulty = floatval($diffJson["difficulty"]);
  else if (isset($diffJson["currentDifficulty"])) $difficulty = floatval($diffJson["currentDifficulty"]);
  else if (isset($diffJson["current_difficulty"])) $difficulty = floatval($diffJson["current_difficulty"]);
}

// Subsidy from height
$halvings = $height > 0 ? intdiv($height, 210000) : 0;
$subsidyBtc = 50.0 / pow(2.0, $halvings);

// Hashrate from difficulty: H ≈ D * 2^32 / 600 :contentReference[oaicite:6]{index=6}
$networkHashrateHs = ($difficulty * pow(2, 32)) / 600.0;

// BTC/day per TH
$blocksPerDay = 144.0;
$btcPerDayPerTH = ($networkHashrateHs > 0)
  ? ($blocksPerDay * $subsidyBtc) / ($networkHashrateHs / 1e12)
  : 0.0;

$usdPerDayPerTH = $btcPerDayPerTH * $usdPerBtc;

echo json_encode([
  "usdPerDayPerTH" => $usdPerDayPerTH,
  "usdPerBtc" => $usdPerBtc,
  "difficulty" => $difficulty,
  "height" => $height,
  "subsidyBtc" => $subsidyBtc,
  "ts" => gmdate("c")
], JSON_PRETTY_PRINT);
