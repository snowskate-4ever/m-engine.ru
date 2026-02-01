# Upload changed VK/post-collection files via FTP using curl.exe
# Credentials from sync_config.jsonc (handyhost). On 530 Access denied - check login/password.
$ErrorActionPreference = 'Continue'
$base = Join-Path $PSScriptRoot '..'
$configPath = Join-Path $base 'sync_config.jsonc'
if (-not (Test-Path $configPath)) {
  Write-Error "sync_config.jsonc not found"
  exit 1
}
$raw = Get-Content $configPath -Raw
$json = $raw -replace '(?m)\s*//[^\n]*', '' -replace '/\*[\s\S]*?\*/', ''
$cfg = ($json | ConvertFrom-Json).handyhost
$ftpHost = $cfg.host
$user = $cfg.username
$pass = $cfg.password
$remotePath = $cfg.remotePath.TrimStart('/')
$ftpBase = "ftp://${ftpHost}/${remotePath}"

$files = @(
  '.env.example',
  'app/Console/Commands/VkClearProcessedRawCommand.php',
  'app/Http/Controllers/TestController.php',
  'app/Http/Controllers/VkFeedController.php',
  'app/Http/Controllers/VkPostsController.php',
  'app/Jobs/FetchVkGroupPostsJob.php',
  'app/Models/VkPost.php',
  'app/Models/VkPostMedia.php',
  'app/Models/VkTracking.php',
  'app/Services/DownloadVkMediaJob.php',
  'app/Services/FetchVkGroupPostsJob.php',
  'app/Services/TestService.php',
  'app/Services/api/VkApiService.php',
  'config/filesystems.php',
  'routes/web.php',
  'resources/views/test/openapi.blade.php',
  'resources/views/vk_feed.blade.php',
  'resources/views/vk_menu.blade.php',
  'resources/views/vk_newsfeed.blade.php',
  'resources/views/vk_posts_index.blade.php',
  'database/migrations/2026_01_28_120000_add_vk_tokens_to_users_table.php',
  'database/migrations/2026_02_01_120000_create_vk_posts_table.php',
  'database/migrations/2026_02_01_120001_create_vk_post_media_table.php',
  'database/migrations/2026_02_01_120002_add_next_from_to_vk_trackings_table.php',
  'docs/QUEUE.md',
  'docs/VK_POSTS_IMPLEMENTATION.md',
  'package.json',
  'package-lock.json'
)

$cred = "${user}:${pass}"
$failed = 0
foreach ($f in $files) {
  $local = Join-Path $base $f
  if (-not (Test-Path $local)) {
    Write-Host "Skip (missing): $f"
    continue
  }
  Write-Host "Upload: $f"
  $url = "$ftpBase/$f" -replace '\\', '/'
  & curl.exe -s -S -T $local $url -u $cred --ftp-create-dirs
  if ($LASTEXITCODE -ne 0) {
    Write-Host "  FAILED (exit $LASTEXITCODE). Check FTP login/password. 530 = Access denied."
    $failed++
  }
  Start-Sleep -Milliseconds 800
}
if ($failed -gt 0) {
  Write-Host "`nTotal failed: $failed"
  exit 1
}
Write-Host "`nDone. Run on server: php artisan migrate"
