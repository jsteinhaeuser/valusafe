# Installing ValuSafe with Docker

This guide takes you from an empty system to a running ValuSafe installation.
It assumes no Docker knowledge, but a machine on which Docker runs.

**As of:** 29 Aug 2026 · applies from version 4.3.3 · run through end to end on a Synology · *(German version: [INSTALL.de.md](INSTALL.de.md))*

---

## 1. Requirements

**Docker with Compose v2.** Check:

```
docker version --format '{{.Server.Version}}'
docker compose version
```

Both commands must print a version number. If the second one reports
`'compose' is not a docker command`, your Docker installation is too old —
ValuSafe needs Compose v2.

**A container network with a connection to the outside.** This is not a
given, and it is the most common reason for a failed build. Check it before
anything else happens:

```
docker run --rm alpine sh -c "ping -c1 -W3 deb.debian.org >/dev/null && echo OK"
```

If you get `OK`, all is well. If you get nothing or an error, read
section 7 first — otherwise the build would abort after several minutes with
`Temporary failure resolving 'deb.debian.org'`.

**About 2 GB of disk space** for the two base images (PHP and MariaDB)
and the built ValuSafe image.

---

## 2. Get the project files

Either clone the repository:

```
git clone https://github.com/jsteinhaeuser/valusafe.git valusafe
cd valusafe
```

Or unpack an archive. Afterwards the directory structure must look like this:

```
valusafe/
├── .dockerignore
├── wert/                     ← the application
├── docker/
│   ├── Dockerfile
│   ├── docker-compose.yml
│   ├── .env.example
│   └── docker/
│       └── entrypoint.sh
└── install/
    └── schema.sql           ← database schema
```

If `install/schema.sql` is missing, the archive is incomplete — without this
file you get an empty database and the application does not start.
The file sits outside `docker/` on purpose: it is the blueprint of the
database for every kind of installation, not just for Docker.

---

## 3. Create the configuration

```
cd docker
cp .env.example .env
```

In `.env`, **three** values have to be set. All others can stay as they are.

### DB_PASS and DB_ROOT_PASS

Two different database passwords. They are never typed in by hand — nobody
needs to remember them. So the simplest and safest way is to have them
generated:

```
DBP=$(openssl rand -hex 24)
DBR=$(openssl rand -hex 24)
grep -v -E '^(DB_PASS|DB_ROOT_PASS|APP_PORT)=' .env > .env.tmp
printf 'DB_PASS=%s\nDB_ROOT_PASS=%s\nAPP_PORT=8080\n' "$DBP" "$DBR" >> .env.tmp
mv .env.tmp .env
unset DBP DBR
```

The port is set to `8080` here; if that port is taken, change it in the
command before running it (see APP_PORT in this section). `printf` passes the
values through unchanged — unlike `sed`, it interprets nothing.

If you would rather choose the passwords yourself: `sed` is a poor fit for
that, because special characters such as `|` or `/` get mistaken for
delimiters. Edit `.env` in a text editor instead.

If `DB_PASS` or `DB_ROOT_PASS` is left empty, `docker compose` aborts with a
message. That is intentional — the configuration used to fall back silently to
the password `changeme`.

### ADMIN_USER

The login name of the first administrator, for example `admin`. This name is
also set as `SUPERADMIN_USERNAME`; **only this account can reach user
management** in the admin area.

Leave `ADMIN_PASS` empty. The container then generates a random password on
its first start and shows it once in the log (see section 5).

### APP_PORT

The default is `8080`. On a NAS or a server running other services this port
is often taken — then enter something like `8090`.

### Check

```
awk -F= '/^(DB_NAME|DB_USER|ADMIN_USER|ADMIN_PASS|APP_PORT)=/{print} \
         /^(DB_PASS|DB_ROOT_PASS)=/{print $1 "=<" length($2) " characters>"}' .env
```

You should see two filled password lines, a set `ADMIN_USER`, an empty
`ADMIN_PASS` and the port you want.

---

## 4. Build and start

```
docker compose build
docker compose up -d
```

The first build downloads the base images and compiles the PHP extensions.
Depending on your connection and machine this takes five to fifteen minutes.
Later builds are much faster.

---

## 5. Pick up the sign-in details

```
docker compose logs app
```

The log contains a box. The container prints it in German; *Benutzer* is the
user name, *Passwort* the password:

```
  ============================================================
   ValuSafe — Administratorkonto angelegt

     Benutzer:  admin
     Passwort:  malVOXeZ02HBtimrI6XEjy7M

   Dieses Passwort wird nur EINMAL angezeigt. Notiere es jetzt
   oder aendere es nach der ersten Anmeldung unter Einstellungen.
  ============================================================
```

(“Administrator account created. This password is shown only ONCE. Write it
down now or change it under Settings after your first sign-in.”)

The password appears **only on this one start**. On every later start the
container just reports that the user table is not empty.

If it gets lost, the only ways out are a complete fresh start (section 7) — or
resetting it through the database.

If `ADMIN_PASS` was set in `.env`, the password does not appear in the log;
the one entered there applies.

---

## 6. First sign-in and acceptance check

Open `http://localhost:8080` in a browser — or the server's IP address and the
port set in `APP_PORT`.

Check four things after signing in:

1. **Sign-in** with the user name and password from the log.
2. **Admin → Users** is reachable (gear icon “Admin” in the left bar, then
   the “Users” tile, “Manage”). If it is not, `ADMIN_USER`
   in `.env` does not match the account you signed in with.
3. **Create an item**, with room and location. That confirms the complete
   schema was loaded.
4. **Set your own password** under Settings, if it was generated.

After that, a look at **Settings** is worthwhile: language and theme.
The name of the instance is set by `APP_NAME` in `.env`.

---

## 7. When something does not work

### “Temporary failure resolving 'deb.debian.org'” during the build

The containers have no connection to the outside. The build cannot succeed
as long as that is the case. Narrow it down:

```
docker run --rm alpine ping -c1 -W3 1.1.1.1
```

If even this fails, the problem is not name resolution but forwarding. Then
check:

```
cat /proc/sys/net/ipv4/ip_forward          # must be 1
iptables -t nat -L POSTROUTING -n -v | grep -i masq
```

If the MASQUERADE counters are at 0, the packets never reach these rules —
a firewall in front of them is blocking.

**On Synology devices** the cause is almost always the DSM firewall. It
creates a chain `FORWARD_FIREWALL` that runs *before* the Docker rules and, at
the end, drops everything it has not explicitly allowed:

```
iptables -S FORWARD_FIREWALL | tail -5
```

Typically there is an exception for your own LAN range, one for a few ports,
and then `-j DROP`. Containers send from `172.17.0.x` to arbitrary ports —
they match none of the exceptions and run into the `DROP`. The Docker rules
behind it are never reached; that is also how you recognise it: the
MASQUERADE counters stay at 0.

The fix is in DSM under **Control Panel → Security → Firewall**, with one
additional rule:

| Field | Value |
|---|---|
| Ports | All |
| Source IP | Subnet `172.16.0.0` / `255.240.0.0` |
| Action | Allow |

The mask covers `172.16.x` to `172.31.x`, i.e. all Docker networks. The rule
must sit **above** the final deny rule.

Do not help things along on the command line with `iptables`: Synology
rebuilds the chains on every restart and on every change in the web
interface, and rules set by hand disappear in the process.

### No password lets you sign in

Usually the schema was not loaded because the database volume already
contained data — Docker runs `init.sql` **only on an empty volume**. The
entrypoint now detects this and aborts with a message; older versions started
silently without an account. Check:

```
docker compose logs app | tail -30
docker compose logs db  | tail -30
```

### Port already in use

```
Error starting userland proxy: listen tcp4 0.0.0.0:8080: bind: address already in use
```

Set a different `APP_PORT` in `.env`, then `docker compose up -d`.

### Complete fresh start

**Deletes all data of this installation** — items, images, users:

```
docker compose down -v
docker compose up -d
```

The `-v` removes the volumes. Without `-v` the database stays, and
`init.sql` again does not run.

---

## 8. Backups

Three things need backing up:

| What | Where |
|---|---|
| Database | volume `db_data` |
| Images | volume `uploads` |
| Documents | volume `documents` |

The application has its own backup under **Admin → Backup**, which writes
the database and files into one archive. For automatic backups there is
`cron_backup.php`; the token it needs is at `backups/.backup_token` in the
volume of the same name after the first start.

---

## 9. What this guide does not cover

- **Running over HTTPS.** This description ends with an installation on your
  own network. If you make ValuSafe reachable from the internet, you need a
  reverse proxy with a certificate in front of it. The entrypoint already
  disables the HTTPS redirect in `.htaccess` so that such a proxy works.
- **phpMyAdmin** is included in `docker-compose.yml`, but only starts with
  `docker compose --profile dev up -d`. Do not use it for regular operation.
- **Updating to a new ValuSafe version** has not been tried yet;
  see the section below.

---

## 10. Updating

Inside the container the application's update button is disabled. A new
version arrives as a newly built image; the data lives in the volumes and
is kept.

First create a backup under **Admin → Backup** and download it.
Then, in the project directory:

```
git pull
cd docker
docker compose build
docker compose up -d
```

If you installed from an archive, unpack the new archive over the old
directory instead; the `.env` in `docker/` stays in place.

New versions sometimes bring changes to the database. The container applies
them itself on every start; the application remembers changes it has already
applied and skips them. What happened is in the log:

```
docker compose logs app | grep -i migration
```

If a change fails, the container does not start and shows the output of the
migration run in the log.

**Exception:** installations set up before 26 Sep 2026 on which no migration
has ever run. There it cannot be determined which changes the schema already
contains, and catching up on all of them can delete location data. The
container then runs nothing and shows a warning instead. In that case, back
up first, then look at what would be applied:

```
docker compose exec app php migrate.php --dry-run
```

and only run the real thing (the same command without `--dry-run`) once it is
clear what it will do.

This procedure follows from how the image and the entrypoint are built, but
it has not yet been played through on an existing installation.

---

## Appendix: how this guide was tested

This guide was run through completely on 29 Aug 2026 — from an empty
directory to the signed-in application. Test environment: Synology NAS,
Container Manager (Docker 24.0.2, Compose 2.20.1), btrfs.

**Confirmed:** building the image, first initialisation of the database with
all tables (22 at the time; today's `install/schema.sql` creates 21), creation of the administrator account with the password shown
in the log, sign-in in the browser, access to user management, creating an
item with room and location, changing the password in the profile.

Three points of this guide only came out of that run: the network check in
section 1 (the containers had no connection to the outside because of a
firewall rule), the waiting time for the first initialisation, and the note
on the DSM firewall in section 7.

**Still not tested** is everything listed in section 9: running behind a
reverse proxy with HTTPS, and updating an existing installation to a newer
ValuSafe version (section 10).
