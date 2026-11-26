# Contributing to ValuSafe

ValuSafe is written and maintained by one person. Contributions are welcome —
bug reports most of all. This file says how to send them and what a change
needs before it can go into a release.

Deutsch: [CONTRIBUTING.de.md](CONTRIBUTING.de.md)

## Reporting a bug or asking a question

Open an issue on GitHub: <https://github.com/jsteinhaeuser/valusafe/issues>.
German or English, both are fine.

Useful in a report:

- what you did, what you expected, and what happened instead
- the ValuSafe version (bottom of the admin area, or `wert/wert-update/version.json`)
- PHP version and web server (Apache, Nginx, Docker)
- the exact error message or a screenshot

**Security problems do not belong in a public issue.** See
[SECURITY.md](SECURITY.md) for where to send them.

## Pull requests

Pull requests are welcome. Two things to know first:

- **Development happens in a private repository.** This public repository is
  updated with each release and with changes to documentation and CI. A pull
  request is therefore not merged directly; the change is applied in the
  development repository by hand and released with the next version. You are
  credited in the commit and in the changelog, unless you prefer not to be.
- **One maintainer, no response time promised.** For anything larger than a
  small fix, please open an issue first and describe what you plan, so that
  nobody spends an evening on a change that does not fit.

## What a change needs

### Code

- Plain PHP 8.2 to 8.5, no framework, no build step. MySQL/MariaDB in
  production; the tests use SQLite.
- Database access only with prepared statements (`$db->select()`,
  `$db->execute()` with parameters). No values glued into SQL.
- Every output escaped (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` or
  `esc()`).
- Every form and every changing request checks the CSRF token; every page
  checks permissions (`requireLogin()`, `requirePermission()`).
- No external resources: no CDN, no web fonts from elsewhere, no third-party
  scripts. The Content Security Policy in `wert/SecurityHeaders.php` forbids
  them, and ValuSafe must work without an internet connection.
- User-facing text goes into the language files (`wert/lang/*.php`, and
  `wert/backend/lang/*.php` for the admin area) — in **all** languages, not
  just German and English. If you cannot translate, use the English text and
  say so in the pull request.
- Keyboard and screen reader: buttons are `<button>`, icons without text get
  an `aria-label`, decorative icons `aria-hidden="true"`. The target is
  WCAG 2.2 AA.
- Comments in German or English. Existing code is mostly commented in German;
  that is fine to keep.

### Tests

**New functionality and bug fixes come with a test.** The tests are in
`wert/tests` (PHPUnit, SQLite in memory, no database server needed):

```bash
cd wert
composer install
vendor/bin/phpunit
```

If a change cannot reasonably be tested automatically (a layout fix, for
instance), say in the pull request how you checked it.

### Static analysis

PHPStan runs on every push (`wert/phpstan.neon.dist`):

```bash
cd wert
vendor/bin/phpstan analyse
```

It must report no errors. Findings that existed when PHPStan was introduced
are listed in `wert/phpstan-baseline.neon`. Please do not add new entries
there — fix the finding instead, or explain in the pull request why it is a
false positive.

### Changelog

Every change that a user notices gets an entry in `wert/changelog_data.php`
(German text plus `text_en`). Technical details go into a separate entry with
`'public' => false`.

## License

ValuSafe is under the MIT License ([LICENSE](LICENSE)). By sending a
contribution you agree that it is published under the same license.
