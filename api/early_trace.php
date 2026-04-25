<?php
/**
 * Early Tracer - Place this at the very top of index.php to find where it dies
 */
function trace($msg) {
    $log = __DIR__ . '/trace.log';
    file_put_contents($log, date('Y-m-d H:i:s') . " - $msg\n", FILE_APPEND);
}

trace("TRACER: Script started");
trace("TRACER: Method: " . $_SERVER['REQUEST_METHOD']);
trace("TRACER: URI: " . $_SERVER['REQUEST_URI']);

// Check if we can even write to a file
if (!is_writable(__DIR__)) {
    header('X-Trace-Error: Dir not writable');
}
?>
