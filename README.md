# Team Notes

A small Laravel 13 REST service for teams that share a notebook. Each member belongs to one team and can create, read, edit, and delete any note in it. The server derives the team from the bearer token on every request.

## Run locally

Install PHP 8.4.1 or newer with PDO SQLite and Composer 2. From a fresh clone, run the following to install dependencies, generate the local application key, create the SQLite file, and migrate it.

```sh
composer setup
php artisan serve
```

The service listens at `http://127.0.0.1:8000`. In another terminal in the same directory, provision two members. The command creates the named team if needed and prints each member's token once. Reusing a member name in the same team fails and preserves their existing token.

```sh
alice=$(php artisan member:create Atlas Alice)
bob=$(php artisan member:create Atlas Bob)
api=http://127.0.0.1:8000/api/notes

curl -sS -X POST "$api" -H "Authorization: Bearer $alice" \
  -H 'Content-Type: application/json' \
  -d '{"title":"Shift handoff","body":"Review the overnight log."}'
```

Use the returned note ID below. Bob can edit Alice's note. A member provisioned under another team receives 404 for that same ID.

```sh
id=1   # replace with the returned id
curl -sS -X PATCH "$api/$id" -H "Authorization: Bearer $bob" \
  -H 'Content-Type: application/json' -d '{"body":"Overnight log reviewed."}'
curl -sS "$api" -H "Authorization: Bearer $alice"
```

## API

All routes below require the bearer token. Send JSON for writes. Note responses contain `id`, `team_id`, `title`, `body`, `created_at`, and `updated_at`.

| Method | Path | Success |
| --- | --- | --- |
| POST | /api/notes | 201 with the saved note |
| GET | /api/notes?page=1 | 200 with up to 25 notes under `data`, newest ID first, and pagination links |
| GET | /api/notes/{id} | 200 with the note |
| PATCH | /api/notes/{id} | 200 with the updated note |
| DELETE | /api/notes/{id} | 204 with an empty body |

Creation requires a nonblank title (up to 200 characters) and body (up to 20,000). PATCH accepts either or both and preserves omitted fields. Missing or invalid tokens receive 401. Missing and cross-team notes both return 404. Invalid note fields receive 422 with errors by field.

## Verify

```sh
php artisan test
```

The feature tests provision real tokens, exercise the HTTP routes, and check stored data using an isolated in-memory SQLite database. They cover teammate edits, cross-team access, attempted team-ID overrides, invalid credentials, duplicate provisioning, and invalid writes.

## Design choices

- **One team per member.** A foreign key on each member and note makes the access rule small enough to inspect in one controller. Every lookup starts with the authenticated member's team. Moving between teams or granting access to individual notes would require a different membership model.
- **Operator-issued tokens.** The command generates 32 random bytes and stores their SHA-256 digest. Team enrollment requires local operator access. This keeps invitations, passwords, and account recovery outside the HTTP API. Use HTTPS when serving bearer tokens beyond localhost.
- **SQLite and partial updates.** One database file keeps setup reproducible. PATCH updates only supplied fields. Concurrent edits to the same field use last-write-wins, and SQLite serializes writes. Both choices suit a low-volume service for small teams.

## With more time

I would add token rotation and revocation so an operator could replace a lost credential. For teams editing simultaneously, I would add a note revision and conditional writes so a stale save can be detected before it overwrites a teammate's edit.
