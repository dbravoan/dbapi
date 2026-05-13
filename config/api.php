<?php

$tenantSupportedVersions = json_decode(env('API_TENANT_SUPPORTED_VERSIONS', '{}'), true);

if (!is_array($tenantSupportedVersions)) {
    $tenantSupportedVersions = [];
}

return [
    'supported_versions' => explode(',', env('API_SUPPORTED_VERSIONS', 'v1')),
    'tenant_supported_versions' => $tenantSupportedVersions,
];
