# Merge status tracker

Internal notes, not part of the Moodle plugin itself — delete before Marketplace submission.

## Phase 1 — Scaffold (DONE, audited)
Post-write audit caught and fixed 2 real issues before this was called done:
- db/install.php was looking up the Authenticated User role by `shortname`,
  which silently fails if an admin renames the role. Fixed to look up by
  `archetype => 'user'` instead, matching the already-validated pattern from
  the original local_mcpbridge/db/install.php.
- db/install.xml had 2 stale table COMMENT strings still describing
  themselves as belonging to "the local_oauth2 plugin" / "webservice_mcp" —
  cosmetic only (Moodle doesn't parse these), but fixed for clarity.
All PHP files pass `php -l`, install.xml is well-formed and passes `xmllint`.

- [x] version.php — component local_placecom_mcp
- [x] db/access.php — merged capabilities (local/placecom_mcp:use, local/placecom_mcp:manage_oauth_clients)
- [x] db/install.xml — all 9 tables renamed to local_placecom_mcp_ prefix
      (8 from local_oauth2: client, user_auth_scope, access_token, authorization_code,
      refresh_token, scope, jwt, public_key; 1 from local_mcpbridge: token_scope)
- [x] db/install.php — auto-grant local/placecom_mcp:use to Authenticated user role
- [x] lang/en/local_placecom_mcp.php — minimal strings (enough to install)
- [x] LICENSE — full GPL v3 text
- [x] README.md

## Phase 2 — OAuth2 core (NOT STARTED)
- [ ] Port classes/ (16 files) from local_oauth2, renamespace local_oauth2\... -> local_placecom_mcp\...
- [ ] Port cli/, vendor/bshaffer/oauth2-server-php, thirdpartylibs.xml
- [ ] Port login.php, token.php, authorize.php-equivalents, jwks.php, manage_oauth_clients.php,
      manage_tokens.php, openid_configuration.php, refresh_token.php, userinfo.php
- [ ] Fix known bug: login.php reads $_POST['code_challenge'] / code_challenge_method directly
      instead of optional_param()
- [ ] Update all SQL/table references to new table names

## Phase 3 — MCP server (NOT STARTED)
- [ ] Port server.php, protected_resource.php from webservice_mcp
- [ ] Port classes/local/*, classes/privacy/provider.php, renamespace
- [ ] Move endpoint: webservice/mcp/server.php -> local/placecom_mcp/server.php
- [ ] Port approved_functions.php allowlist (36 functions) and readOnlyHint annotations

## Phase 4 — Bridge removal (NOT STARTED)
- [ ] Port local_mcpbridge_token_scope table logic and cleanup task only
- [ ] Delete/replace observers.php's token-mirroring — issue webservice token directly
      at OAuth grant time instead of mirroring into external_tokens separately

## Phase 5 — Privacy (NOT STARTED)
- [ ] Merge local_oauth2's and webservice_mcp's privacy providers into one
- [ ] Explicitly cover local_placecom_mcp_token_scope (previously undocumented
      in local_mcpbridge, which had no privacy provider at all)

## Phase 6 — Testing (NOT STARTED)
- [ ] Fresh Moodle install, confirm clean install with no debug warnings
- [ ] Full OAuth login -> token -> tools/list -> tools/call round trip
- [ ] PostgreSQL compatibility test (MySQL-only so far)
- [ ] Run moodle-plugin-ci

## Phase 7 — Marketplace readiness (NOT STARTED)
- [ ] Public issue tracker (GitHub Issues on this repo)
- [ ] Short + full plugin descriptions
- [ ] Screenshots
- [ ] Documentation URL
- [ ] Repo renamed to moodle-local_placecom_mcp convention
- [ ] Disclose MCP/external-AI-service dependency in plugin description
