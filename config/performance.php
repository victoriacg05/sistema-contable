<?php

return [
    'slow_request_ms' => (int) env('APP_SLOW_REQUEST_MS', 2000),
    'slow_query_ms' => (int) env('DB_SLOW_QUERY_MS', 500),
    'log_slow_queries' => (bool) env('DB_LOG_SLOW_QUERIES', false),
];
