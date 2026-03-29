/**
 * Выгрузка на сервер по FTP через curl.
 * Использует sync_config.jsonc (первый профиль или --profile=name).
 * Исключает node_modules, vendor, .git, .env и т.д. (как deploy-ftp.js).
 *
 * Запуск: node scripts/deploy-ftp-curl.js
 *        node scripts/deploy-ftp-curl.js --profile=handyhost
 *        node scripts/deploy-ftp-curl.js app/Http path/to/file.php  (только указанные файлы/папки)
 */
import { readFileSync, existsSync, statSync } from 'fs';
import { join } from 'path';
import { readdirSync } from 'fs';
import { fileURLToPath } from 'url';
import { spawnSync } from 'child_process';

const __dirname = fileURLToPath(new URL('.', import.meta.url));
const rootDir = join(__dirname, '..');

function loadSyncConfig(profileName = null) {
  const configPath = join(rootDir, 'sync_config.jsonc');
  if (!existsSync(configPath)) return null;
  let content = readFileSync(configPath, 'utf8');
  content = content.replace(/\s*\/\/[^\n]*/g, '').replace(/\s*\/\*[\s\S]*?\*\//g, '');
  const data = JSON.parse(content);
  const keys = Object.keys(data).filter((k) => typeof data[k] === 'object' && data[k].host);
  if (keys.length === 0) return null;
  const key = profileName && data[profileName] ? profileName : keys[0];
  return data[key];
}

const IGNORE_DIRS = new Set([
  'node_modules', 'vendor', '.git', '.github', '.cursor', '.idea', '.vscode', '.fleet', '.nova', '.zed',
  'storage/logs', 'storage/framework/cache', 'storage/framework/sessions', 'storage/framework/views', 'storage/framework/testing',
]);
const IGNORE_FILES = new Set(['.env', '.env.backup', '.env.production', '.phpunit.cache', '.phpunit.result.cache']);
const IGNORE_PREFIX = ['.env.', 'Homestead.', 'npm-debug', 'yarn-error', 'sync_config'];

function shouldIgnore(relPath) {
  const parts = relPath.replace(/\\/g, '/').split('/');
  for (let i = 1; i <= parts.length; i++) {
    const sub = parts.slice(0, i).join('/');
    if (IGNORE_DIRS.has(sub)) return true;
  }
  const base = parts[parts.length - 1];
  if (IGNORE_FILES.has(base)) return true;
  if (IGNORE_PREFIX.some((p) => base.startsWith(p))) return true;
  if (base.endsWith('.tmp') || base.endsWith('.log')) return true;
  return false;
}

function* walk(dir, base = '') {
  const full = join(dir, base);
  if (!existsSync(full)) return;
  const entries = readdirSync(full, { withFileTypes: true });
  for (const e of entries) {
    const rel = base ? `${base}/${e.name}` : e.name;
    if (e.isDirectory()) {
      if (shouldIgnore(rel)) continue;
      yield* walk(dir, rel);
    } else {
      if (shouldIgnore(rel)) continue;
      yield rel;
    }
  }
}

function collectFilesFromArg(arg) {
  const full = join(rootDir, arg);
  if (!existsSync(full)) return [];
  const rel = arg.replace(/\\/g, '/');
  const out = [];
  if (statSync(full).isFile()) {
    if (!shouldIgnore(rel)) out.push(rel);
    return out;
  }
  for (const f of walk(rootDir, rel)) out.push(f);
  return out;
}

function main() {
  const profileName = process.argv.find((a) => a.startsWith('--profile='))?.slice('--profile='.length);
  const onlyArgs = process.argv.slice(2).filter((a) => !a.startsWith('--'));
  const cfg = loadSyncConfig(profileName);
  if (!cfg || !cfg.host || !cfg.username || !cfg.password) {
    console.error('FTP config not found. Create sync_config.jsonc with host, username, password (and remotePath).');
    process.exit(1);
  }

  const host = cfg.host;
  const user = cfg.username;
  const password = cfg.password;
  const port = cfg.port || 21;
  const remotePath = (cfg.remotePath || '/').replace(/\/$/, '') || '';
  const useFtps = cfg.secure === true || cfg.type === 'ftps';
  const protocol = useFtps ? 'ftps' : 'ftp';

  const files = onlyArgs.length
    ? onlyArgs.flatMap((a) => collectFilesFromArg(a))
    : [...walk(rootDir)];

  if (files.length === 0) {
    console.log('No files to upload.');
    return;
  }

  const baseUrl = `${protocol}://${host}:${port}/${remotePath ? remotePath + '/' : ''}`;
  console.log('Uploading', files.length, 'files via curl to', host + ':' + port, remotePath || '/');

  let ok = 0;
  let err = 0;
  for (const rel of files) {
    const localPath = join(rootDir, rel);
    const remoteFile = rel.replace(/\\/g, '/');
    const url = baseUrl + remoteFile;

    const args = [
      '--ftp-create-dirs',
      '--silent',
      '--show-error',
      '-T', localPath,
      url,
      '-u', `${user}:${password}`,
    ];
    if (useFtps) args.push('--insecure');

    const r = spawnSync('curl', args, { stdio: 'pipe', encoding: 'utf8', shell: process.platform === 'win32' });
    if (r.status === 0) {
      ok++;
      if (ok % 50 === 0 || ok + err === files.length) console.log(ok + '/' + files.length);
    } else {
      err++;
      console.error('FAIL', rel, r.stderr || r.error?.message || r.status);
    }
  }
  console.log('Done. Uploaded:', ok, err ? ', failed: ' + err : '');
  if (err) process.exit(1);
}

main();
