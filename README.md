# Placecom MCP Connector

`local_placecom_mcp` is a Moodle plugin that lets AI assistants supporting the
[Model Context Protocol](https://modelcontextprotocol.io) (MCP), such as Claude,
securely access Moodle on behalf of a signed-in user. It bundles everything needed in
a single plugin:

- an **OAuth 2.0 / OpenID Connect authorization server**, so users sign in to Moodle
  and approve the assistant's access;
- an **MCP server** that exposes a curated, read-mostly set of Moodle web service
  functions to the approved assistant.

Every request runs as the user who approved it, so Moodle's own permission checks
always apply. The assistant never sees more than that user could see in Moodle.

## Features

- MCP server endpoint (JSON-RPC over HTTP) with `tools/list` and `tools/call`.
- A fixed allowlist of **24 web service functions** (22 read, 2 write). Anything not on
  the list is refused, even if called directly by name.
- OAuth 2.0 authorization code flow with refresh tokens, PKCE (`S256`, which can be
  required per client), and signed OpenID Connect ID tokens (RS256).
- Standard discovery documents (OpenID configuration and protected resource metadata)
  and a JWKS endpoint, so compliant assistants can configure themselves.
- Scope-based access control: `moodle_mcp_read` and `moodle_mcp_write`. Write functions
  are refused for tokens that were not granted the write scope.
- Administration pages to register OAuth clients, view and revoke issued tokens, and
  switch the MCP server on or off.
- Scheduled tasks that remove expired and orphaned token records.

## Requirements

- Moodle 4.5 or later.
- Web services enabled: *Site administration → Advanced features → Enable web services*. This is off on a
  new Moodle site. When it is off, the MCP endpoint refuses every request.
- The PHP OpenSSL extension (used once at install time to generate the signing key pair).
  On some Windows servers (for example XAMPP) PHP cannot find OpenSSL's configuration file and
  key generation fails. If that happens, set `$CFG->opensslcnf` in `config.php` to the full path of
  your `openssl.cnf` (the same setting Moodle core uses), then run
  `php local/placecom_mcp/cli/generate_keys.php`.
- To connect a hosted assistant such as Claude, your Moodle site must be reachable
  from the internet over HTTPS. Hosted assistants cannot reach `localhost`.

The plugin has no dependencies on other Moodle plugins and does not require Composer.
Its one third-party library is bundled (see [Third-party libraries](#third-party-libraries)).

## Installation

**From a ZIP file**

1. Sign in as an administrator and go to *Site administration → Plugins → Install plugins*.
2. Upload the plugin ZIP (the top-level folder inside it must be named `placecom_mcp`)
   and follow the on-screen steps.

**Manually**

1. Copy the plugin folder to `local/placecom_mcp` inside your Moodle directory.
2. Visit *Site administration → Notifications*, or run `php admin/cli/upgrade.php`.

On installation the plugin:

- creates its database tables (all prefixed `local_placecom_mcp_`);
- adds the default OAuth/OpenID scopes plus `moodle_mcp_read` and `moodle_mcp_write`;
- generates an RSA key pair used to sign ID tokens;
- registers an external service named **Placecom MCP Service** containing the allowlisted
  functions;
- grants the `local/placecom_mcp:use` capability to the *Authenticated user* role.

The plugin is not ready to use yet: complete the setup steps under *Connecting an AI assistant* below (enable web services, then register an OAuth client).

## Configuration

Everything is under *Site administration → Server → Placecom MCP Connector*.

| Setting | Description |
| --- | --- |
| Enable MCP server | Master switch for the MCP endpoint. On by default. When off, the endpoint refuses every request. |
| Web service ID to bridge | Optional. Leave blank to use *Placecom MCP Service*. Set the numeric ID of another external service if you want tokens attached to that one instead. |
| Access token lifetime | How long an access token stays valid. Default: 1 hour. |
| Refresh token lifetime | How long a refresh token stays valid. Default: 1 week. |
| Issuer | Optional. Overrides the issuer URL, for example when Moodle runs behind a reverse proxy. Defaults to the site URL. |

The same section contains **Manage OAuth clients** and **Manage active tokens**, available to
users holding the `local/placecom_mcp:manage_oauth_clients` capability (Managers by default).

## Connecting an AI assistant

1. Enable web services: *Site administration → Advanced features → Enable web services*. This is off on a new Moodle site, and the MCP endpoint refuses every request while it is off.
2. Go to *Manage OAuth clients* and register a client for the assistant. Enter the
   redirect URI that the assistant gives you, and choose the scopes it may request.
   Grant `moodle_mcp_write` only if you want it to be able to post to forums.
3. In the assistant, add a custom MCP connector pointing at your MCP endpoint, and
   supply the client ID (and secret, if the client has one).
4. When the user first connects, they sign in to Moodle and approve the access request.

### Endpoints

All URLs are relative to your Moodle site URL.

| Purpose | Path |
| --- | --- |
| MCP server | `/local/placecom_mcp/server.php` |
| Protected resource metadata | `/local/placecom_mcp/protected_resource.php` |
| OpenID configuration (discovery) | `/local/placecom_mcp/openid_configuration.php` |
| Authorization | `/local/placecom_mcp/login.php` |
| Token | `/local/placecom_mcp/token.php` |
| User info | `/local/placecom_mcp/userinfo.php` |
| JWKS | `/local/placecom_mcp/jwks.php` |

### Discovery addresses

The plugin publishes its sign-in settings at `/local/placecom_mcp/openid_configuration.php`. Some MCP
clients instead look for the standard `/.well-known/...` addresses. If your client cannot find the sign-in
settings, point those addresses at the same page. For Apache, with Moodle at `/moodle`, add this to the
server configuration (adjust the path, or drop `/moodle` if Moodle is at the web root):

```
RewriteEngine On
RewriteRule ^/\.well-known/(oauth-authorization-server|openid-configuration)/moodle/?$ /moodle/local/placecom_mcp/openid_configuration.php [PT,L]
RewriteRule ^/moodle/\.well-known/(oauth-authorization-server|openid-configuration)/?$ /moodle/local/placecom_mcp/openid_configuration.php [PT,L]
```

## What the assistant can access

The allowlist lives in `classes/local/approved_functions.php` and is the single source
of truth. It covers, in outline:

- courses, course categories and course module details;
- the user's enrolled and recent courses, groups, and group members;
- calendar action events and notification counts;
- course completion status and the user's badges;
- pages and URL resources;
- assignments, quizzes and lessons (course-level details only),
  and the forums in a course (not their discussions or posts).

Only two functions can change data: `mod_forum_add_discussion` and
`mod_forum_add_discussion_post`. They require the `moodle_mcp_write` scope.

The list is intentionally narrow. In particular it contains no general user-lookup or
user-search functions (`core_user_*`). To add or remove a function, edit the list and
increase the plugin version so Moodle re-syncs the external service on upgrade.

## Security

- Keep Moodle debugging off in production (*Site administration → Development → Debugging* set to *None*, with *Display debug messages* off). Debug output can be printed into MCP responses, corrupting them and exposing internal details.
- Each call is executed as the authenticated user, with Moodle's normal capability
  checks applied on top of the allowlist.
- Write access is enforced separately from read access, using the scope recorded when
  the token was issued.
- OAuth client secrets are stored hashed.
- Administrators can switch the MCP endpoint off at any time and can revoke tokens from
  the *Manage active tokens* page. Revoking a token takes effect immediately and also removes all of that user's
  other MCP tokens, so they must sign in again.

## Privacy

The plugin stores the following in the Moodle database:

- OAuth clients registered by administrators;
- authorization codes, access tokens and refresh tokens, each linked to the Moodle user
  who approved the access;
- the scopes each user has granted to each client;
- records linking an issued web service token to the scope it was granted.

The plugin implements the Moodle Privacy API. It declares the data above, including the data
released to a connected assistant, and supports exporting and deleting a user's data through
Moodle's data request tools.

**Data leaves your site when you connect an assistant.** The plugin itself does not send
data to any third party. However, whatever the allowlisted functions return for a
connected user is delivered to the assistant, and from there it is handled under that
assistant provider's own terms and privacy policy. Decide which assistants to connect,
and which scopes to grant, with this in mind and in line with your institution's data
protection obligations.

## Costs and external services

The plugin is free. It needs no subscription, licence key or API key from the plugin
provider, and it does not call any external AI service itself. The AI assistant you
connect is a separate product, chosen and configured by you, and may have its own plans
and terms.

## Third-party libraries

| Library | Version | Licence | Location |
| --- | --- | --- | --- |
| [bshaffer/oauth2-server-php](https://github.com/bshaffer/oauth2-server-php) | v1.14.1 | MIT | `vendor/bshaffer/oauth2-server-php/` |

It is declared in `thirdpartylibs.xml`.

## Support

Report bugs and request features at
<https://github.com/GOJO22G/moodle-local_placecom_mcp/issues>.

## Credits and licence

Developed by AlmaBay Networks Pvt. Ltd. (Placecom). This plugin brings together three
earlier plugins into one, and builds on the work of others:

- The OAuth 2.0 / OpenID Connect server is based on
  [`local_oauth2`](https://moodle.org/plugins/local_oauth2) by Enovation Solutions,
  which was itself forked from `projectestac/moodle-local_oauth`. It has been
  substantially modified for this plugin.
- The MCP server is based on `webservice_mcp` by MohammadReza PourMohammad
  (`onbirdev/moodle-webservice_mcp`).

Original copyright notices are preserved in each file, with Placecom's added alongside.

Licensed under the [GNU GPL v3 or later](LICENSE).
