# Security Policy

ValuSafe is a self-hosted inventory application for valuables. It is written
and maintained by one person. This file says where to send a security report
and what to expect afterwards — including the parts that are less comfortable
than a corporate policy would be.

## Reporting a vulnerability

Send an email to **valusafe@palindrom.de**. German or English, both are fine.

Please do not open a public issue or post the details anywhere before the
problem is fixed.

Useful in a report:

- what the problem is, in one or two sentences
- the file and, if you have it, the line
- the steps that reproduce it
- the ValuSafe version you looked at (see `wert-update/version.json`)
- what an attacker could do with it — your judgement, even if rough

If you would like to send the report encrypted, say so in a first, empty mail
and we will arrange a key. There is no published PGP key at the moment; a key
nobody has ever used is worse than none.

## What to expect

This is not a company. There is no on-call rotation and no ticket system.

- Acknowledgement: usually within a few days.
- An assessment of whether it is a real problem: within about two weeks.
- A fix: as fast as the severity warrants, but honestly — a long trip or a
  bad week can delay it. If that happens you will be told, rather than left
  waiting.

If you hear nothing at all for three weeks, assume the mail got lost and send
it again.

## Scope

**In scope** — the source code shipped in the release package: the
application in the repository root, `backend/`, `ajax/`, `components/`,
`install/setup.php` and `install/schema.sql`.

**Out of scope:**

- Instances run by other people. They are other people's servers.
- The maintainer's own instances and the internal administration hub. If you
  find something there, it is still welcome — but report it, do not test
  against it.
- The public demo instance at `valusafe.2ix.de`. Its login credentials are
  published on purpose, on the project website, so that anyone can try the
  application. That the demo can be logged into with known credentials is not
  a finding.
- Missing rate limits or hardening on a deliberately open demo.
- Anything that requires physical access to the server, or an administrator
  acting against their own installation.

## Supported versions

Only the most recent release receives fixes. There are no backports to older
versions — with one maintainer, a second maintained branch would mean neither
gets the attention it needs.

The current released version is in
[`wert/wert-update/version.json`](wert/wert-update/version.json).

## After a report

A confirmed vulnerability is fixed, released, and recorded in the changelog.
The changelog entry describes what was wrong and what changed; it does not
quietly call a security fix an "improvement".

You will be credited by name if you want to be, and not if you do not. Please
say which.

There is no bug bounty. Nothing is paid for a report. That is stated here so
that nobody spends an evening on ValuSafe expecting otherwise.

## What this file is not

ValuSafe is not currently registered with a CVE Numbering Authority, and the
source repository is not public yet. A report will therefore be fixed and
documented, but it will not receive a CVE identifier. This may change; until
it does, saying so plainly seems better than implying a process that does not
exist.

---

Deutsche Fassung: [SECURITY.de.md](SECURITY.de.md)
