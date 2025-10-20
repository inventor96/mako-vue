<?php
$head_file = file_get_contents(__DIR__ . '/../../../../.git/HEAD');
if (strpos($head_file, 'ref: ') === 0) {
    $ref_file = trim(substr($head_file, 5));
    if (file_exists(__DIR__.'/../../../../.git/' . $ref_file)) {
        $commit_hash = trim(file_get_contents(__DIR__.'/../../../../.git/' . $ref_file));
    } else {
        // this fallback will only be used if the git commit hash cannot be found.
        // this will cause a full page load on almost every request, so it's not ideal.
        // hopefully this will only be used when you first create your repo, and never again once you've made your first commit.
        $commit_hash = date('YmdHis');
    }
} else {
    $commit_hash = trim($head_file);
}
return [$commit_hash];