# EPDC Conversations - Add Git Ignore

Date: 2026-05-31

## Summary
Added a repository-level `.gitignore` file for generated dependencies and common local development artifacts.

## Created Files
- `.gitignore`
- `.context/2026-05-31-add-gitignore.md`

## Ignored Paths
- `vendor/`
- `node_modules/`
- local cache files for PHPUnit and PHP CS Fixer
- editor folders for VS Code and PhpStorm
- macOS `.DS_Store`
- root log files

## Notes
- Kept `composer.lock` trackable.
- Did not ignore source assets, block files, or plugin templates.
