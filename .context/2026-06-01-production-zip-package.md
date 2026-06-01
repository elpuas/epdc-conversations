# Production Zip Package

Date: 2026-06-01

## Goal

Create a production-ready installable plugin archive at `dist/epdc-conversations.zip`.

## Commands Used

```bash
mkdir -p dist
mkdir -p /private/tmp/epdc-conversations-dist
rsync -a ./ /private/tmp/epdc-conversations-dist/epdc-conversations/ \
  --exclude .git \
  --exclude .github \
  --exclude .context \
  --exclude tests \
  --exclude node_modules \
  --exclude AGENTS.md \
  --exclude phpcs.xml.dist \
  --exclude .DS_Store \
  --exclude dist
composer install --no-dev --optimize-autoloader --working-dir=/private/tmp/epdc-conversations-dist/epdc-conversations
zip -qry /Users/alfredonavas/Studio/agentic-development/wp-content/plugins/epdc-conversations/dist/epdc-conversations.zip epdc-conversations
```

## Additional Staging Cleanup

Removed repo-only artifacts from the staging copy before creating the final zip:

- `.codex/`
- `.gitignore`
- `.distignore`
- `README.md`
- `composer.json`
- `composer.lock`

## Validation Done

1. Verified the archive exists:
   - `test -f dist/epdc-conversations.zip && echo exists`
2. Verified zip integrity:
   - `unzip -t dist/epdc-conversations.zip`
3. Verified archive root structure:
   - `unzip -Z1 dist/epdc-conversations.zip | sort`
   - Confirmed the zip root folder is `epdc-conversations/`.
4. Verified required production files are included:
   - `assets/`
   - `src/`
   - `templates/`
   - `vendor/`
   - `blocks/`
   - `readme.txt`
   - `epdc-conversations.php`
5. Verified excluded development paths are not present:
   - `.git`
   - `.github`
   - `.context`
   - `tests`
   - `node_modules`
   - `AGENTS.md`
   - `phpcs.xml.dist`
   - `.DS_Store`
6. Verified installable WordPress plugin structure:
   - Root plugin directory present.
   - Main bootstrap file present at `epdc-conversations/epdc-conversations.php`.
   - Archive data passed `unzip -t` with no errors.

## Zip Path

`dist/epdc-conversations.zip`

## Issues Found

1. The first zip attempt overlapped with dependency pruning in the staging directory and failed. Re-running the zip after the staging copy was finalized succeeded.
2. `composer install --no-dev --optimize-autoloader` warned that `composer.lock` is not up to date with `composer.json`, but the staging install completed and generated the production autoloader successfully.
