<?php

// values can be 'buy', 'sell', or 'both'
$stocks = [
    'BND'  => 'sell',
    'EDV'  => 'sell',
    'VCLT' => 'buy',

    'VTI'  => 'sell',
    'MGK'  => 'buy',

    'VFH'  => 'sell',
    'VGT'  => 'buy',

    'VXUS' => 'sell',
    'VSS'  => 'buy',
    'VWO'  => 'buy',
];
$stock = @$argv[1] ?: array_rand($stocks);

list($low, $high) = getBBands($stock);
$current = getCurrent($stock);

echo "Stock: $stock\n";
echo "Goal: $stocks[$stock]\n";
echo "Low: $low\n";
echo "Current: $current\n";
echo "High: $high\n";

if ($current > $high && $stocks[$stock] != 'buy') {
    $alert = "Sell $stock ($current > $high)";
} elseif ($current < $low && $stocks[$stock] != 'sell') {
    $alert = "Buy $stock ($current < $low)";
} else {
    $alert = false;
}

if ($alert) {
    $response = sendAlert($alert);
    header('Content-Type: text/plain');
    echo $response;
}

// helper functions

function getBBands($stock) {
    $query = http_build_query([
        'function'      => 'BBANDS',
        'symbol'        => $stock,
        'interval'      => 'daily',
        'time_period'   => 21,
        'series_type'   => 'close',
        'nbdevup'       => 2,
        'nbdevdn'       => 1,
        'matype'        => 1,   // EMA
        'apikey'        => $_ENV['ALPHA_VANTAGE_API_KEY'],
    ]);

    $ch = curl_init('https://www.alphavantage.co/query?' . $query);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    file_put_contents(strtolower($stock).'.bbands.json', $response);

    $json   = json_decode($response, true);
    $latest = $json['Meta Data']['3: Last Refreshed'];
    $latest = $json['Technical Analysis: BBANDS'][$latest];

    return [$latest['Real Lower Band'], $latest['Real Upper Band']];
}

function getCurrent($stock) {
    $interval = '15min';
    $query = http_build_query([
        'function'      => 'TIME_SERIES_INTRADAY',
        'symbol'        => $stock,
        'interval'      => $interval,
        'apikey'        => $_ENV['ALPHA_VANTAGE_API_KEY'],
    ]);

    $ch = curl_init('https://www.alphavantage.co/query?' . $query);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    file_put_contents(strtolower($stock).'.tsi.json', $response);

    $json   = json_decode($response, true);
    $latest = $json['Meta Data']['3. Last Refreshed'];
    $latest = $json["Time Series ($interval)"][$latest];

    return $latest['4. close'];
}

function sendAlert($alert) {
    if (! @$_ENV['TILL_URL'] || ! @$_ENV['RECIPIENT_PHONE']) {
        // we can't SMS, so just echo
        return $alert;
    }

    $ch = curl_init($_ENV['TILL_URL']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'phone' => [$_ENV['RECIPIENT_PHONE']],
        'text'  => $alert,
    ]));
    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}

