/**
 * FTP deploy: uploads project to server (excluding node_modules, vendor, .git, .env, etc.)
 * Reads settings from sync_config.jsonc (first profile or --profile=name).
 * При ошибке "maximum number of clients" автоматически повторяет попытку после паузы.
 * Run: npm run deploy
 */
import { Client } from 'basic-ftp';
import { readFileSync, existsSync } from 'fs';
import { join, dirname } from 'path';
import { readdirSync } from 'fs';
import { fileURLToPath } from 'url';

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

const MAX_RETRIES = 5;
const RETRY_DELAY_SEC = 25;

function isMaxClientsError(err) {
  const msg = (err && err.message || '').toLowerCase();
  return msg.includes('maximum number of clients') || msg.includes('too many connections') || msg.includes('too many users') || /530|421/.test(String(err.code));
}

const __dirname = fileURLToPath(new URL('.', import.meta.url));
const rootDir = join(__dirname, '..');

// Load sync_config.jsonc (strip // and /* */ comments for JSONC)
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

async function main() {
  const profileName = process.argv.find((a) => a.startsWith('--profile='))?.slice('--profile='.length);
  const onlyFiles = process.argv.slice(2).filter((a) => !a.startsWith('--') && existsSync(join(rootDir, a)));
  const cfg = loadSyncConfig(profileName);
  if (!cfg || !cfg.host || !cfg.username || !cfg.password) {
    console.error('FTP config not found. Add sync_config.jsonc with host, username, password (and remotePath).');
    console.error('Or run: npm run deploy -- --profile=handyhost');
    process.exit(1);
  }
  const host = cfg.host;
  const user = cfg.username;
  const password = cfg.password;
  const port = cfg.port || 21;
  const remotePath = (cfg.remotePath || '/').replace(/\/$/, '') || '/';
  const secure = cfg.type === 'sftp' || cfg.secure === true;

  const client = new Client(60_000);
  client.ftp.verbose = process.env.FTP_VERBOSE === '1';

  let lastError = null;
  for (let attempt = 1; attempt <= MAX_RETRIES; attempt++) {
    try {
      if (attempt > 1) {
        console.log('Retry', attempt + '/' + MAX_RETRIES + '...');
      }
      console.log('Connecting to', host + ':' + port, '...');
      await client.access({
        host,
        port,
        user,
        password,
        secure,
        secureOptions: { rejectUnauthorized: false },
      });
      await client.cd(remotePath);
      console.log('Uploading to', remotePath, onlyFiles.length ? '(' + onlyFiles.length + ' files)' : '...');

      const files = onlyFiles.length ? onlyFiles : [...walk(rootDir)];
      let done = 0;
      for (const rel of files) {
        const localPath = join(rootDir, rel);
        const remoteDir = dirname(rel).replace(/\\/g, '/');
        const remoteFile = rel.replace(/\\/g, '/');
        if (remoteDir !== '.') {
          const parts = remoteDir.split('/').filter(Boolean);
          for (const part of parts) {
            try {
              await client.cd(part);
            } catch (_) {
              await client.ensureDir(part);
              await client.cd(part);
            }
          }
          await client.cd(remotePath);
        }
        await client.uploadFrom(localPath, remoteFile);
        done++;
        if (done % 50 === 0 || done === files.length) {
          console.log(done + '/' + files.length);
        }
      }
      console.log('Done. Uploaded', files.length, 'files.');
      return;
    } catch (err) {
      lastError = err;
      try {
        client.close();
      } catch (_) {}
      if (attempt < MAX_RETRIES && isMaxClientsError(err)) {
        console.warn('FTP:', err.message);
        console.warn('Waiting', RETRY_DELAY_SEC, 'sec before retry (free slot on server)...');
        await sleep(RETRY_DELAY_SEC * 1000);
      } else {
        break;
      }
    }
  }
  console.error('FTP error:', lastError?.message || lastError);
  process.exit(1);
}

main();
