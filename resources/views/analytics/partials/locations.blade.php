@include('partials.province-chart', [
    'provinceCounts' => array_combine($charts['locations']['labels'], $charts['locations']['values']),
    'provinceIds' => ['box' => 'locationsBox', 'canvas' => 'chart-locations', 'extras' => 'locationsDonutExtras', 'total' => 'locationsTotal', 'legend' => 'locationsLegend', 'message' => 'locationsMessage'],
])
