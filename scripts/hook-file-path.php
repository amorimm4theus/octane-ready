<?php
// Reads the hook JSON from stdin and prints tool_input.file_path (empty if missing).
$input = json_decode(stream_get_contents(STDIN), true) ?: [];
echo $input['tool_input']['file_path'] ?? '';
