# Merge status tracker

Internal notes, not part of the Moodle plugin itself — delete before Marketplace submission.

## Phase 1 — Scaffold (DONE, audited)
Post-write audit caught and fixed 1 real issue before this was called done:
- db/install.xml had 2 stale table COMMENT strings still describing
  themselves as belonging to "the local_oauth2 plugin" / "webservice_mcp" —
  cosmetic only (Moodle doesn't parse these), but fixed for clarity.
All PHP files pass `php -l`, install.xml is well-formed and passes `xmllint`.
(A second issue — db/install.php's role lookup — was also caught at the time,
but that whole file was later deleted for a different, more serious reason;
see the real-install bug note below.)

- [x] version.php — component local_placecom_mcp
- [x] db/access.php — merged capabilities (local/placecom_mcp:use, local/placecom_mcp:manage_oauth_clients)
- [x] db/install.xml — all 9 tables renamed to local_placecom_mcp_ prefix
      (8 from local_oauth2: client, user_auth_scope, access_token, authorization_code,
      refresh_token, scope, jwt, public_key; 1 from local_mcpbridge: token_scope)
- [x] local/placecom_mcp:use auto-grant to Authenticated user — REMOVED as a db/install.php
      hook (see real-install bug below) and now handled via db/access.php's own
      'archetypes' => ['user' => CAP_ALLOW], the same mechanism manage_oauth_clients
      already used for the manager role.
- [x] db/install.php — RECREATED (not the same file as the one removed above). Its job is
      now scope-seeding + RSA keypair generation only, ported from local_oauth2's real
      db/install.php, which Phase 2 never actually read (see regression below).
- [x] lang/en/local_placecom_mcp.php — minimal strings (enough to install)
- [x] LICENSE — full GPL v3 text
- [x] README.md

**Real bug found via actual install testing (not caught by static checks):**
install crashed with "Capability 'local/placecom_mcp:use' was not found!" —
db/install.php was calling assign_capability() for this plugin's own capability
during its own install, but Moodle only registers a plugin's declared capabilities
*after* running that plugin's install.php, not before. This differs from the
local_mcpbridge pattern it was modeled on, which safely granted a capability
belonging to an *already-installed, separate* plugin — self-granting during
one's own install is a different, broken case that no amount of static
linting/grepping would have caught, since the bug is about install-sequence
timing, not syntax or content. Fixed by moving the grant to db/access.php's
archetypes (correct timing, synced automatically) and deleting db/install.php
entirely — but see the next entry: that deletion was too broad.

**Regression found via testing, caused by the fix above:** deleting db/install.php
outright also deleted logic that had nothing to do with the capability bug and
was never actually ported in the first place. Phase 2 copied local_oauth2's
classes, endpoints, and lang strings, but never actually opened local_oauth2's
own db/install.php to check what it did — a real gap in that phase's process,
not just an unlucky side effect of this fix. That file seeds 8 default OAuth2/
OIDC scopes (openid, profile, email, offline_access, address, phone, plus
moodle_mcp_read/moodle_mcp_write - the two this whole project's MCP read/write
permission model depends on) and generates the default RSA key pair used to
sign OpenID Connect ID tokens. Without it, local_placecom_mcp_scope was
completely empty after install - any client requesting a non-hardcoded scope
would hit invalid_scope, and ID token signing had no default key at all.
Recreated db/install.php with just this logic (properly renamespaced),
keeping the capability grant out of it as the earlier fix intended.
Deliberately did NOT port local_mcpbridge's lib.php on-login defensive
re-seeding (local_mcpbridge_seed_oauth_scopes(), called from
classes/observers.php) - that existed only to cover install-order not being
guaranteed *between separate plugins* in the old 3-plugin setup. With OAuth2
and the MCP scopes now seeded by the same plugin's own install.php, that
ordering problem doesn't exist, so the defensive fallback is dropped as
unneeded rather than ported.

## Phase 2 — OAuth2 core (DONE, audited)
- [x] Ported classes/ (16 files), renamespaced local_oauth2\... -> local_placecom_mcp\...
- [x] Ported cli/generate_keys.php, vendor/bshaffer/oauth2-server-php (untouched, verified
      byte-identical to original via diff -rq), thirdpartylibs.xml
- [x] Ported login.php, jwks.php, manage_oauth_clients.php, manage_tokens.php,
      openid_configuration.php, refresh_token.php, settings.php, token.php, userinfo.php
- [x] Fixed the $_POST bug: login.php's PKCE param merge now reads via optional_param()
      instead of indexing $_POST directly
- [x] All 8 local_oauth2_* table references confirmed renamed (grep-verified in
      moodle_oauth_storage.php, utils.php, privacy/provider.php)
- [x] Merged lang/en/local_oauth2.php strings into lang/en/local_placecom_mcp.php;
      dropped 2 now-duplicate/orphaned keys (pluginname, oauth2:manage_oauth_clients)
      and 1 pre-existing typo in the original ('prvacy:metadata:...', dead duplicate key)
- [x] db/tasks.php ported (cleanup task registration) — local_mcpbridge's own task
      (cleanup_orphaned_scope) still needs merging in here during Phase 4
- Post-write audit caught 1 real bug: my mechanical rename script walked the whole
  plugin directory (not just newly-copied files) and incorrectly rewrote 2 doc-comment
  lines in the already-correct Phase 1 files (version.php, db/access.php) that were
  meant to say "local_oauth2" as the origin plugin's name, not self-reference the new
  plugin. Fixed both back to the correct origin reference.
- All PHP files pass `php -l` (vendor/ excluded, correctly untouched)

## Phase 3 — MCP server (DONE, audited)
- [x] Ported classes/local/*.php (4 files: approved_functions, request, server, tool_provider),
      lib.php, locallib.php, server.php (root entrypoint), protected_resource.php, pix/icon.png,
      and the 4 test files, all renamespaced webservice_mcp -> local_placecom_mcp
- [x] Architectural fix (found by reading, not by lint): server.php's on/off gate used
      webservice_protocol_is_enabled('mcp') - Moodle's registry of enabled webservice
      PROTOCOLS, which only exists for "webservice" type plugins. Once this became a
      "local" type plugin, that check would have permanently 403'd every request,
      forever, regardless of any setting - no protocol named "mcp" exists to check
      anymore. Replaced with a real plugin setting instead: new "Enable MCP server"
      checkbox in settings.php (local_placecom_mcp/enable_mcp_server, default on),
      checked via get_config() in server.php.
- [x] Fixed 3 hardcoded old-path references that a component-name rename alone would
      NOT catch (found by grepping specifically for '/webservice/mcp' path strings,
      not just the component name): protected_resource.php's own 'resource' field,
      and - found deeper, inside classes/local/server.php's exception_handler - the
      WWW-Authenticate header's resource_metadata URL sent on every 401. Both now
      point at /local/placecom_mcp/... instead of the dead /webservice/mcp/... path.
- [x] Privacy provider: webservice_mcp's classes/privacy/provider.php was DELIBERATELY
      NOT ported. It implements null_provider ("this plugin stores no personal data"),
      which is only true for webservice_mcp in isolation - the merged plugin, as a
      whole, does process personal data via its OAuth2 core. A class can't implement
      both null_provider and a real metadata provider at once, so local_oauth2's
      already-ported real provider (from Phase 2) is the one correct provider for the
      merged plugin. This is a drop, not a merge.
- [x] Lang strings merged, dropping 3 keys that would have collided with or been
      superseded by what Phase 1/2 already defined: pluginname, mcp:use (old capability
      display string), and privacy:metadata (belonged to the now-dropped null_provider).
- [x] Real bug found via post-write audit (not caught by lint): classes/local/server.php's
      enforce_scope() - the actual read/write permission enforcement, gating every
      write-type MCP function call - queried the literal table name
      'local_mcpbridge_token_scope'. That table was already renamed to
      local_placecom_mcp_token_scope back in Phase 1's install.xml, but this specific
      reference survived because it's a cross-plugin dependency baked into
      webservice_mcp's OWN code (added by Placecom during original integration), not
      something covered by either Phase 2's local_oauth2 rename pass or Phase 3's
      webservice_mcp rename pass - neither transform script's substitution list
      included 'local_mcpbridge'. Found by grepping the whole plugin post-port for
      ALL THREE original plugin names, not just the one being ported that phase.
      Confirmed the field names it uses (token, scope) do match the actual schema
      before calling this fixed. Fixed: table name corrected.
- [x] Copyright: added Placecom's copyright line alongside the original author's in
      the 8 files that didn't already have it (request.php, tool_provider.php, lib.php,
      locallib.php, and the 4 test files) - some other files in this phase (server.php,
      protected_resource.php, approved_functions.php) already carried Placecom's
      copyright from the original repo, since Placecom had already modified those
      in-house before this merge.
- All PHP files pass `php -l`. Cross-checked every get_string()/moodle_exception() call
  against local_placecom_mcp against the actual lang file - all keys exist, none missing.
- 2 more historical comments logged for the Phase 4 cleanup list (see below): one in
  classes/local/approved_functions.php, referencing local_mcpbridge's db/services.php
  by name - accurate today, will be stale once Phase 4 removes it as a separate thing.

## Phase 4 — Bridge removal (DONE, audited)
- [x] Ported classes/observers.php (the actual token-mirroring logic: OAuth access
      tokens -> external_tokens, so they become valid wstokens Moodle's native
      webservice_base_server::authenticate_by_token() accepts) and
      classes/task/cleanup_orphaned_scope.php, both renamespaced
- [x] Created db/events.php (registers the 3 observers against this plugin's own
      access_token_created/updated/revoked events - now the same plugin watching
      its own events, which is unusual but not wrong)
- [x] Created db/services.php (declares the external service Moodle auto-creates
      on install; renamed from "MCP Bridge Service"/mcpbridge_service to "Placecom
      MCP Service"/placecom_mcp_service, per the naming decided back in Phase 1
      planning; reads its function list from the already-ported
      approved_functions::LIST - verified that constant actually exists under that
      exact name before referencing it)
- [x] Merged the cleanup_orphaned_scope task into db/tasks.php alongside the
      existing cleanup task, same daily-3am schedule as the original
- [x] Added the serviceid override setting to settings.php + 3 merged lang strings
- [x] Manually renamed 2 hardcoded strings a blanket rename would NOT have caught
      (same class of miss as Phase 3's hardcoded paths): the service shortname
      'mcpbridge_service' -> 'placecom_mcp_service', in both observers.php and
      db/services.php

**Serious near-miss, found by re-reading the ported file after transforming it, not
by lint (lint cannot catch a call to a function that doesn't exist but is
syntactically valid to call):** the ported observers.php still called
`local_mcpbridge_seed_oauth_scopes()` from lib.php - a defensive scope re-seeding
helper - inside handle_access_token_created_or_updated(). That helper was
DELIBERATELY never ported (decided back in the install.php recreation earlier:
seeding now happens once, correctly, at install time, so the on-login fallback is
redundant). But porting observers.php via the mechanical rename script left the
CALL SITE in place while the function it calls doesn't exist anywhere in this
codebase - which would have thrown a fatal "call to undefined function" error on
every single OAuth login. This would have broken the exact login flow the partner
had just confirmed working end-to-end in Phase 3 testing. Caught by re-reading the
whole file after the transform rather than trusting the transform's output, then
confirmed the fix was complete by scanning the entire codebase for any other
local_placecom_mcp_* prefixed function call without a matching definition
anywhere (found one more hit, which turned out to be a class instantiation, not
a function call - verified, not a bug).

**Why the underlying bridging mechanism was ported as-is, not simplified:**
MERGE_STATUS previously floated "issue the webservice token directly at OAuth
grant time instead of mirroring via an observer" as a cleaner long-term design.
Given a partner was actively blocked on this exact gap during live testing, the
event-observer mechanism ported here is the same one already proven correct in
the original 3-plugin setup (per this project's own history, 2 real bugs were
already found and fixed in this exact code before this merge project began) -
safer to restore proven working code under time pressure than to design and
trust new, untested architecture. The "even simpler, no separate observer"
refactor remains a valid future improvement, not something to gamble on now.

- All PHP files pass `php -l`. Cross-checked every get_string() call has a
  matching lang key. Scanned the whole codebase for any other undefined-function
  risk of the same shape as the bug above - none found.


- [ ] Port local_mcpbridge_token_scope table logic and cleanup task only
- [ ] Delete/replace observers.php's token-mirroring — issue webservice token directly
      at OAuth grant time instead of mirroring into external_tokens separately

## Phase 5 — Privacy (NOT STARTED)
- [ ] Merge local_oauth2's and webservice_mcp's privacy providers into one
- [ ] Explicitly cover local_placecom_mcp_token_scope (previously undocumented
      in local_mcpbridge, which had no privacy provider at all)

## Phase 6 — Testing (PARTIAL — Phases 1-2 verified live)
- [x] Fresh install actually tested by partner on real Moodle — passed, after 2 real bugs
      found and fixed (capability self-grant timing crash; missing scope-seeding +
      RSA keypair generation). Confirmed: local_placecom_mcp_scope has 8 rows,
      local_placecom_mcp_public_key has 1 row with client_id = ''.
- [ ] OAuth login -> token -> tools/list -> tools/call round trip — can't test yet,
      MCP server itself isn't ported in (that's Phase 3, not started)
- [ ] PostgreSQL compatibility test (MySQL-only so far)
- [ ] Run moodle-plugin-ci

## Phase 7 — Marketplace readiness (NOT STARTED)
- [ ] Public issue tracker (GitHub Issues on this repo)
- [ ] Short + full plugin descriptions
- [ ] Screenshots
- [ ] Documentation URL
- [ ] Repo renamed to moodle-local_placecom_mcp convention
- [ ] Disclose MCP/external-AI-service dependency in plugin description
